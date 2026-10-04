String refundDisplayStatus(String? status, String? changeBy) {
  if (changeBy == 'seller' && (status == 'approved' || status == 'rejected')) {
    return 'Awaiting administrator review (vendor recommends ${status == 'approved' ? 'approval' : 'rejection'})';
  }
  return status ?? 'pending';
}
