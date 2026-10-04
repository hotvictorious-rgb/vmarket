import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/models/pickup_payment_state.dart';
import 'package:provider/provider.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/controllers/checkout_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/models/pickup_reservation_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/screens/digital_payment_order_place_screen.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/screens/payment_status_screen.dart';

class PickupPaymentScreen extends StatefulWidget {
  final PickupReservationModel reservation;
  const PickupPaymentScreen({super.key, required this.reservation});
  @override State<PickupPaymentScreen> createState() => _PickupPaymentScreenState();
}
class _PickupPaymentScreenState extends State<PickupPaymentScreen> {
  bool _useCashback = false;
  bool _busy = false;
  String? _error;
  Future<void> _review() async {
    final controller = context.read<CheckoutController>();
    final code = widget.reservation.reservationCode ?? '';
    if (controller.pendingPickupPayment != null) {
      _status(controller.pendingPickupPayment!.reservationCode); return;
    }
    setState(() { _busy = true; _error = null; });
    final response = await controller.quotePickupReservation(reservationCode: code, useCashback: _useCashback);
    if (!mounted) return;
    setState(() => _busy = false);
    final data = response.response?.data;
    if (response.response?.statusCode != 200 || !validPickupQuote(data)) {
      setState(() => _error = response.error?.toString() ?? 'Unable to prepare the quote. Please try again.'); return;
    }
    final quote = data['quote'] as Map;
    final confirmed = await showDialog<bool>(context: context, builder: (dialogContext) => AlertDialog(
      title: const Text('Confirm pickup payment'),
      content: Column(mainAxisSize: MainAxisSize.min, crossAxisAlignment: CrossAxisAlignment.start, children: [
        for (final row in {'Merchandise': 'merchandise_subtotal', 'Tax': 'tax_total', 'Shipping': 'shipping_total', 'Cashback applied': 'cashback_amount', 'Total to pay': 'total_amount'}.entries)
          Text('${row.key}: ${quote['currency']} ${quote[row.value]}'),
        Text('Quote expires: ${quote['expires_at']}'),
      ]),
      actions: [TextButton(onPressed: () => Navigator.pop(dialogContext, false), child: const Text('Cancel')),
        TextButton(onPressed: () => Navigator.pop(dialogContext, true), child: const Text('Confirm payment'))],
    ));
    if (!mounted || confirmed != true) return;
    setState(() => _busy = true);
    final result = await controller.payPickupReservation(reservationCode: code, quoteToken: data['quote_token'].toString(), useCashback: _useCashback);
    if (!mounted) return;
    final resultData = result.response?.data;
    final url = resultData is Map ? resultData['authorization_url']?.toString() : null;
    if (url != null && url.isNotEmpty) {
      Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => DigitalPaymentOrderPlaceScreen(paymentUrl: url, isPickupPayment: true, reservationCode: code)));
    } else { _status(code); }
  }
  void _status(String code) => Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => PaymentStatusScreen(reservationCode: code, isPickup: true)));
  @override Widget build(BuildContext context) => Scaffold(
    appBar: AppBar(title: const Text('Pickup payment')),
    body: Padding(padding: const EdgeInsets.all(20), child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
      Text(widget.reservation.shop?.name ?? 'Victorious Store'),
      Text('Reservation: ${widget.reservation.reservationCode}'),
      Text('Expires: ${widget.reservation.expiresAt ?? ''}'),
      const SizedBox(height: 16),
      const Text('Review your final quote before confirming payment. Your pickup code appears after payment is verified.'),
      SwitchListTile(title: const Text('Use available Victorious Cashback'), value: _useCashback, onChanged: _busy ? null : (value) => setState(() => _useCashback = value)),
      if (_error != null) Text(_error!),
      ElevatedButton(onPressed: _busy ? null : _review, child: Text(_busy ? 'Please wait…' : 'Review payment')),
      TextButton(onPressed: () => _status(widget.reservation.reservationCode ?? ''), child: const Text('Check payment status')),
    ])),
  );
}

