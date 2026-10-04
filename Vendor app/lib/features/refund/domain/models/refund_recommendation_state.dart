String refundRecommendationMessage(String? status, String? actor) {
  if (status == 'refunded') return 'Refund payment completed.';
  if (actor == 'admin') {
    if (status == 'rejected') return 'The administrator declined this refund.';
    if (status == 'approved') return 'The administrator approved this refund. Payment is pending.';
    return 'This refund is under administrator review.';
  }
  if (actor == 'seller' && (status == 'approved' || status == 'rejected')) {
    return 'Your recommendation is awaiting administrator review. The administrator controls refund payment.';
  }
  return 'The administrator controls refund decisions and payment.';
}
