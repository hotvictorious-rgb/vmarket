<?php

namespace App\Services;

use App\Models\Seller;
use App\Utils\ImageManager;
use Illuminate\Support\Facades\Log;

class NigerianKycService
{
    /**
     * Calculate name similarity percentage between two names
     *
     * @param string|null $name1
     * @param string|null $name2
     * @return float
     */
    public function calculateNameSimilarity(?string $name1, ?string $name2): float
    {
        if (empty($name1) || empty($name2)) {
            return 0.0;
        }

        // Normalize: uppercase, remove special chars, trim
        $clean1 = strtoupper(preg_replace('/[^A-Za-z0-9 ]/', '', $name1));
        $clean2 = strtoupper(preg_replace('/[^A-Za-z0-9 ]/', '', $name2));

        if ($clean1 === $clean2) {
            return 100.0;
        }

        // Tokenized matching (handles reversed names like "OKONKWO VICTOR" vs "VICTOR OKONKWO")
        $tokens1 = array_filter(explode(' ', $clean1));
        $tokens2 = array_filter(explode(' ', $clean2));

        $matchedTokens = 0;
        foreach ($tokens1 as $t1) {
            foreach ($tokens2 as $t2) {
                if ($t1 === $t2 || (strlen($t1) > 3 && strlen($t2) > 3 && levenshtein($t1, $t2) <= 1)) {
                    $matchedTokens++;
                    break;
                }
            }
        }

        $tokenScore = (count($tokens1) > 0) ? ($matchedTokens / max(count($tokens1), count($tokens2))) * 100.0 : 0.0;

        // String similarity
        similar_text($clean1, $clean2, $stringPercent);

        return round(max($tokenScore, $stringPercent), 1);
    }

    /**
     * Clean corporate suffixes to extract the core brand/company name
     *
     * @param string $name
     * @return string
     */
    public function cleanCorporateName(string $name): string
    {
        $suffixes = [
            '/\bLIMITED\b/i',
            '/\bLTD\b/i',
            '/\bPLC\b/i',
            '/\bENTERPRISES?\b/i',
            '/\bVENTURES?\b/i',
            '/\bGLOBAL\b/i',
            '/\bNIGERIA\b/i',
            '/\bNIG\b/i',
            '/\bSERVICES?\b/i',
            '/\bINTEGRATED\b/i',
            '/\bINTERNATIONAL\b/i',
            '/\bSTORES?\b/i',
            '/\bCOMPANY\b/i',
            '/\bCO\b/i',
            '/\bAND\b/i',
            '/\b&\b/i',
        ];

        $cleaned = preg_replace($suffixes, ' ', $name);
        return trim(preg_replace('/\s+/', ' ', $cleaned));
    }

    /**
     * Calculate comprehensive match score across both Personal Name and Shop/Company Name
     *
     * @param Seller $seller
     * @return array
     */
    public function evaluateSellerBankMatch(Seller $seller): array
    {
        $bankHolderName = $seller->holder_name ?? '';
        if (empty($bankHolderName)) {
            return ['score' => 0.0, 'type' => 'none', 'details' => 'No bank account linked'];
        }

        $vendorPersonalName = trim(($seller->f_name ?? '') . ' ' . ($seller->l_name ?? ''));
        $shopName = $seller->shop->name ?? '';

        // 1. Check against Personal Name
        $personalScore = $this->calculateNameSimilarity($vendorPersonalName, $bankHolderName);

        // 2. Check against Shop / Corporate Name
        $cleanBankName = $this->cleanCorporateName($bankHolderName);
        $cleanShopName = $this->cleanCorporateName($shopName);
        $corporateScore = $this->calculateNameSimilarity($cleanShopName, $cleanBankName);

        if ($corporateScore > $personalScore && $corporateScore >= 70.0) {
            return [
                'score' => $corporateScore,
                'type' => 'corporate',
                'details' => "Matched with Registered Shop Name: '$shopName'",
            ];
        }

        return [
            'score' => $personalScore,
            'type' => 'individual',
            'details' => "Matched with Personal Name: '$vendorPersonalName'",
        ];
    }

    /**
     * Submit Vendor KYC details
     *
     * @param Seller $seller
     * @param array $data
     * @param mixed|null $ninFile
     * @param mixed|null $cacFile
     * @return array
     */
    public function submitKyc(Seller $seller, array $data, $ninFile = null, $cacFile = null): array
    {
        $updateData = [];

        if (!empty($data['nin'])) {
            $updateData['nin'] = trim($data['nin']);
        }

        if (!empty($data['cac_number'])) {
            $updateData['cac_number'] = strtoupper(trim($data['cac_number']));
        }

        // Upload NIN Document
        if ($ninFile) {
            if (!empty($seller->nin_document)) {
                $updateData['nin_document'] = ImageManager::update('seller/kyc/', $seller->nin_document, 'webp', $ninFile);
            } else {
                $updateData['nin_document'] = ImageManager::upload('seller/kyc/', 'webp', $ninFile);
            }
        }

        // Upload CAC Document
        if ($cacFile) {
            if (!empty($seller->cac_document)) {
                $updateData['cac_document'] = ImageManager::update('seller/kyc/', $seller->cac_document, 'webp', $cacFile);
            } else {
                $updateData['cac_document'] = ImageManager::upload('seller/kyc/', 'webp', $cacFile);
            }
        }

        $matchResult = $this->evaluateSellerBankMatch($seller);

        $updateData['kyc_status'] = 'submitted';
        $updateData['updated_at'] = now();

        Seller::where('id', $seller->id)->update($updateData);

        return [
            'status' => true,
            'message' => 'KYC verification details submitted successfully.',
            'name_match_percentage' => $matchResult['score'],
            'match_type' => $matchResult['type'],
            'match_details' => $matchResult['details'],
            'kyc_status' => 'submitted',
        ];
    }

    /**
     * Get Seller KYC summary
     *
     * @param Seller $seller
     * @return array
     */
    public function getKycSummary(Seller $seller): array
    {
        $matchResult = $this->evaluateSellerBankMatch($seller);

        return [
            'kyc_status' => $seller->kyc_status ?? 'pending',
            'nin' => $seller->nin ? substr($seller->nin, 0, 3) . '*****' . substr($seller->nin, -3) : null,
            'nin_raw' => $seller->nin,
            'cac_number' => $seller->cac_number,
            'has_nin_document' => !empty($seller->nin_document),
            'has_cac_document' => !empty($seller->cac_document),
            'nin_document_url' => !empty($seller->nin_document) ? asset('storage/app/public/seller/kyc/' . $seller->nin_document) : null,
            'cac_document_url' => !empty($seller->cac_document) ? asset('storage/app/public/seller/kyc/' . $seller->cac_document) : null,
            'bank_holder_name' => $seller->holder_name ?? '',
            'bank_name' => $seller->bank_name,
            'account_no' => $seller->account_no,
            'name_match_score' => $matchResult['score'],
            'match_type' => $matchResult['type'],
            'match_details' => $matchResult['details'],
            'is_verified' => ($seller->kyc_status === 'verified'),
        ];
    }
}
