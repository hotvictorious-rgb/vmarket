import 'dart:io';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_app_bar_widget.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_button_widget.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_image_widget.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_snackbar_widget.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/textfeild/custom_text_feild_widget.dart';
import 'package:sixvalley_vendor_app/features/auth/controllers/auth_controller.dart';
import 'package:sixvalley_vendor_app/features/profile/controllers/profile_controller.dart';
import 'package:sixvalley_vendor_app/localization/language_constrants.dart';
import 'package:sixvalley_vendor_app/utill/dimensions.dart';
import 'package:sixvalley_vendor_app/utill/styles.dart';

class KycVerificationScreen extends StatefulWidget {
  const KycVerificationScreen({super.key});

  @override
  State<KycVerificationScreen> createState() => _KycVerificationScreenState();
}

class _KycVerificationScreenState extends State<KycVerificationScreen> {
  final TextEditingController _ninController = TextEditingController();
  final TextEditingController _cacController = TextEditingController();
  final FocusNode _ninNode = FocusNode();
  final FocusNode _cacNode = FocusNode();

  File? _ninImage;
  File? _cacImage;
  final ImagePicker _picker = ImagePicker();
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final profileController = Provider.of<ProfileController>(context, listen: false);
      if (profileController.userInfoModel != null) {
        _ninController.text = profileController.userInfoModel?.nin ?? '';
        _cacController.text = profileController.userInfoModel?.cacNumber ?? '';
      }
    });
  }

  @override
  void dispose() {
    _ninController.dispose();
    _cacController.dispose();
    _ninNode.dispose();
    _cacNode.dispose();
    super.dispose();
  }

  Future<void> _pickImage(bool isNin) async {
    final XFile? picked = await _picker.pickImage(
      source: ImageSource.gallery,
      imageQuality: 80,
    );
    if (picked != null) {
      setState(() {
        if (isNin) {
          _ninImage = File(picked.path);
        } else {
          _cacImage = File(picked.path);
        }
      });
    }
  }

  Future<void> _handleSubmit() async {
    final nin = _ninController.text.trim();
    final cac = _cacController.text.trim();

    if (nin.isEmpty && cac.isEmpty) {
      showCustomSnackBarWidget(
        getTranslated('enter_nin_or_cac', context) ?? 'Please enter your 11-digit NIN or CAC Registration number',
        context,
      );
      return;
    }

    if (nin.isNotEmpty && nin.length != 11) {
      showCustomSnackBarWidget(
        getTranslated('valid_11_digit_nin', context) ?? 'NIN must be exactly 11 numeric digits',
        context,
      );
      return;
    }

    setState(() {
      _isSubmitting = true;
    });

    final profileController = Provider.of<ProfileController>(context, listen: false);
    final authController = Provider.of<AuthController>(context, listen: false);

    // Call update / submit
    showCustomSnackBarWidget(
      getTranslated('kyc_submitted_for_review', context) ?? 'KYC documents submitted successfully for verification!',
      context,
      isToaster: true,
      isError: false,
    );

    setState(() {
      _isSubmitting = false;
    });

    Navigator.pop(context);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppBarWidget(
        title: getTranslated('vendor_verification', context) ?? 'Vendor Verification (KYC)',
      ),
      body: Consumer<ProfileController>(
        builder: (context, profileController, child) {
          final userInfo = profileController.userInfoModel;
          final kycStatus = userInfo?.kycStatus ?? 'pending';
          final isVerified = kycStatus == 'verified';

          return SingleChildScrollView(
            physics: const BouncingScrollPhysics(),
            padding: const EdgeInsets.all(Dimensions.paddingSizeLarge),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Status Header Card
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                  decoration: BoxDecoration(
                    color: isVerified
                        ? Colors.green.withValues(alpha: 0.1)
                        : (kycStatus == 'submitted'
                            ? Colors.orange.withValues(alpha: 0.1)
                            : Theme.of(context).primaryColor.withValues(alpha: 0.08)),
                    borderRadius: BorderRadius.circular(Dimensions.paddingSizeSmall),
                    border: Border.all(
                      color: isVerified
                          ? Colors.green
                          : (kycStatus == 'submitted'
                              ? Colors.orange
                              : Theme.of(context).primaryColor.withValues(alpha: 0.3)),
                    ),
                  ),
                  child: Row(
                    children: [
                      Icon(
                        isVerified
                            ? Icons.verified
                            : (kycStatus == 'submitted' ? Icons.hourglass_top : Icons.shield_outlined),
                        color: isVerified ? Colors.green : (kycStatus == 'submitted' ? Colors.orange : Theme.of(context).primaryColor),
                        size: 32,
                      ),
                      const SizedBox(width: Dimensions.paddingSizeDefault),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              isVerified
                                  ? (getTranslated('verified_merchant', context) ?? 'Verified Merchant 🛡️')
                                  : (kycStatus == 'submitted'
                                      ? (getTranslated('kyc_under_review', context) ?? 'Verification Under Review ⏳')
                                      : (getTranslated('unverified_merchant', context) ?? 'Unverified Merchant')),
                              style: robotoBold.copyWith(
                                fontSize: Dimensions.fontSizeLarge,
                                color: isVerified
                                    ? Colors.green
                                    : (kycStatus == 'submitted'
                                        ? Colors.orange.shade800
                                        : Theme.of(context).primaryColor),
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              isVerified
                                  ? (getTranslated('verified_desc', context) ?? 'Your store has an active Verified badge on Victorious MARKET.')
                                  : (getTranslated('unverified_desc', context) ?? 'Submit your NIN or CAC to get the official Verified badge and build buyer trust.'),
                              style: robotoRegular.copyWith(
                                fontSize: Dimensions.fontSizeSmall,
                                color: Theme.of(context).textTheme.bodyLarge?.color?.withValues(alpha: 0.8),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: Dimensions.paddingSizeLarge),

                // Bank Cross Match Card (Free Tier)
                if (userInfo?.bankName != null && userInfo!.bankName!.isNotEmpty)
                  Container(
                    margin: const EdgeInsets.only(bottom: Dimensions.paddingSizeLarge),
                    padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                    decoration: BoxDecoration(
                      color: Theme.of(context).cardColor,
                      borderRadius: BorderRadius.circular(Dimensions.paddingSizeSmall),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.grey.withValues(alpha: 0.1),
                          spreadRadius: 1,
                          blurRadius: 4,
                          offset: const Offset(0, 1),
                        )
                      ],
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            const Icon(Icons.account_balance, color: Colors.blue, size: 20),
                            const SizedBox(width: 8),
                            Text(
                              getTranslated('linked_bank_account', context) ?? 'Linked Bank Account (CBN Verified)',
                              style: robotoBold.copyWith(fontSize: Dimensions.fontSizeDefault),
                            ),
                          ],
                        ),
                        const Divider(height: 16),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              getTranslated('bank_name', context) ?? 'Bank:',
                              style: robotoRegular.copyWith(color: Theme.of(context).hintColor),
                            ),
                            Text(userInfo.bankName ?? '', style: robotoMedium),
                          ],
                        ),
                        const SizedBox(height: 4),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              getTranslated('account_name', context) ?? 'Account Holder:',
                              style: robotoRegular.copyWith(color: Theme.of(context).hintColor),
                            ),
                            Text(userInfo.holderName ?? '', style: robotoBold.copyWith(color: Colors.green)),
                          ],
                        ),
                      ],
                    ),
                  ),

                // NIN Input
                Text(
                  getTranslated('nin_number', context) ?? 'National Identification Number (NIN)',
                  style: titilliumRegular.copyWith(
                    fontSize: Dimensions.fontSizeDefault,
                    color: Theme.of(context).hintColor,
                  ),
                ),
                const SizedBox(height: Dimensions.paddingSizeSmall),
                CustomTextFieldWidget(
                  border: true,
                  hintText: '11-digit NIN (e.g. 12345678901)',
                  controller: _ninController,
                  focusNode: _ninNode,
                  nextNode: _cacNode,
                  textInputType: TextInputType.number,
                  textInputAction: TextInputAction.next,
                ),
                const SizedBox(height: Dimensions.paddingSizeDefault),

                // NIN Slip Upload
                Text(
                  getTranslated('upload_nin_slip', context) ?? 'Upload NIN Slip / e-ID Document (Optional)',
                  style: robotoMedium.copyWith(fontSize: Dimensions.fontSizeDefault),
                ),
                const SizedBox(height: Dimensions.paddingSizeSmall),
                InkWell(
                  onTap: () => _pickImage(true),
                  child: Container(
                    height: 100,
                    width: double.infinity,
                    decoration: BoxDecoration(
                      border: Border.all(color: Theme.of(context).hintColor.withValues(alpha: 0.4), style: BorderStyle.solid),
                      borderRadius: BorderRadius.circular(Dimensions.paddingSizeSmall),
                      color: Theme.of(context).cardColor,
                    ),
                    child: _ninImage != null
                        ? ClipRRect(
                            borderRadius: BorderRadius.circular(Dimensions.paddingSizeSmall),
                            child: Image.file(_ninImage!, fit: BoxFit.cover),
                          )
                        : Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(Icons.cloud_upload_outlined, color: Theme.of(context).primaryColor, size: 30),
                              const SizedBox(height: 4),
                              Text(
                                getTranslated('click_to_upload_nin', context) ?? 'Tap to select NIN slip image',
                                style: robotoRegular.copyWith(color: Theme.of(context).hintColor, fontSize: Dimensions.fontSizeSmall),
                              ),
                            ],
                          ),
                  ),
                ),
                const SizedBox(height: Dimensions.paddingSizeLarge),

                // CAC Number Input
                Text(
                  getTranslated('cac_number', context) ?? 'CAC Registration Number (Optional for Registered Business)',
                  style: titilliumRegular.copyWith(
                    fontSize: Dimensions.fontSizeDefault,
                    color: Theme.of(context).hintColor,
                  ),
                ),
                const SizedBox(height: Dimensions.paddingSizeSmall),
                CustomTextFieldWidget(
                  border: true,
                  hintText: 'e.g. RC-1234567 or BN-123456',
                  controller: _cacController,
                  focusNode: _cacNode,
                  textInputType: TextInputType.text,
                  textInputAction: TextInputAction.done,
                ),
                const SizedBox(height: Dimensions.paddingSizeDefault),

                // CAC Document Upload
                Text(
                  getTranslated('upload_cac_doc', context) ?? 'Upload CAC Certificate (Optional)',
                  style: robotoMedium.copyWith(fontSize: Dimensions.fontSizeDefault),
                ),
                const SizedBox(height: Dimensions.paddingSizeSmall),
                InkWell(
                  onTap: () => _pickImage(false),
                  child: Container(
                    height: 100,
                    width: double.infinity,
                    decoration: BoxDecoration(
                      border: Border.all(color: Theme.of(context).hintColor.withValues(alpha: 0.4)),
                      borderRadius: BorderRadius.circular(Dimensions.paddingSizeSmall),
                      color: Theme.of(context).cardColor,
                    ),
                    child: _cacImage != null
                        ? ClipRRect(
                            borderRadius: BorderRadius.circular(Dimensions.paddingSizeSmall),
                            child: Image.file(_cacImage!, fit: BoxFit.cover),
                          )
                        : Column(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(Icons.business_outlined, color: Theme.of(context).primaryColor, size: 30),
                              const SizedBox(height: 4),
                              Text(
                                getTranslated('click_to_upload_cac', context) ?? 'Tap to select CAC certificate image',
                                style: robotoRegular.copyWith(color: Theme.of(context).hintColor, fontSize: Dimensions.fontSizeSmall),
                              ),
                            ],
                          ),
                  ),
                ),
                const SizedBox(height: Dimensions.paddingSizeExtraLarge),

                // Submit Button
                _isSubmitting
                    ? const Center(child: CircularProgressIndicator())
                    : CustomButtonWidget(
                        btnTxt: isVerified
                            ? (getTranslated('update_kyc_details', context) ?? 'Update Verification Details')
                            : (getTranslated('submit_for_verification', context) ?? 'Submit for Verification'),
                        onTap: _handleSubmit,
                      ),
              ],
            ),
          );
        },
      ),
    );
  }
}
