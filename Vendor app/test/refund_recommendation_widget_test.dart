import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:provider/provider.dart';
import 'package:sixvalley_vendor_app/features/refund/controllers/refund_controller.dart';
import 'package:sixvalley_vendor_app/features/refund/domain/models/refund_details_model.dart';
import 'package:sixvalley_vendor_app/features/refund/domain/services/refund_service_interface.dart';
import 'package:sixvalley_vendor_app/features/refund/widgets/approve_reject_widget.dart';
import 'package:sixvalley_vendor_app/theme/controllers/theme_controller.dart';
import 'package:sixvalley_vendor_app/services/storage_service.dart';

class NoCallsService implements RefundServiceInterface {
  @override dynamic noSuchMethod(Invocation invocation) => throw StateError('No network call expected');
}
class DecisionController extends RefundController {
  final RefundDetailsModel details;
  DecisionController(String status, String? actor): details = RefundDetailsModel(refundRequest: [
    RefundRequest(status: status, changeBy: actor, refundStatus: [])]),
    super(refundServiceInterface: NoCallsService());
  @override RefundDetailsModel? get refundDetailsModel => details;
}
void main() {
  for (final status in ['pending', 'approved', 'rejected', 'refunded']) {
    testWidgets('vendor can recommend only pending request: $status', (tester) async {
      await tester.pumpWidget(ChangeNotifierProvider<ThemeController>(create: (_) => ThemeController(storageService: StorageService()), child: ChangeNotifierProvider<RefundController>(
        create: (_) => DecisionController(status, 'seller'),
        child: const MaterialApp(home: Scaffold(body: ApprovedAndRejectWidget())))));
      expect(find.text('Recommend approval'), status == 'pending' ? findsOneWidget : findsNothing);
      expect(find.text('Recommend rejection'), status == 'pending' ? findsOneWidget : findsNothing);
      if (status == 'refunded') expect(find.text('Refund payment completed.'), findsOneWidget);
      if (status == 'approved' || status == 'rejected') expect(find.textContaining('awaiting administrator review'), findsOneWidget);
    });
  }
  testWidgets('administrator decision cannot be changed by vendor', (tester) async {
    await tester.pumpWidget(ChangeNotifierProvider<RefundController>(
      create: (_) => DecisionController('pending', 'admin'),
      child: const MaterialApp(home: Scaffold(body: ApprovedAndRejectWidget()))));
    expect(find.text('Recommend approval'), findsNothing);
    expect(find.text('Recommend rejection'), findsNothing);
  });
  for (final status in ['approved', 'rejected']) {
    testWidgets('administrator $status shows final decision without vendor controls', (tester) async {
      await tester.pumpWidget(ChangeNotifierProvider<RefundController>(
        create: (_) => DecisionController(status, 'admin'),
        child: const MaterialApp(home: Scaffold(body: ApprovedAndRejectWidget()))));
      expect(find.text('Recommend approval'), findsNothing);
      expect(find.text('Recommend rejection'), findsNothing);
      expect(find.textContaining(status == 'approved' ? 'Payment is pending' : 'declined'), findsOneWidget);
    });
  }
}
