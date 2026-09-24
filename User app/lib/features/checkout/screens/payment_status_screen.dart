import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_app_bar_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/screens/pickup_order_success_screen.dart';
import 'package:flutter_sixvalley_ecommerce/features/dashboard/screens/dashboard_screen.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/utill/app_constants.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';
import 'package:flutter_sixvalley_ecommerce/di_container.dart' as di;
import 'package:flutter_sixvalley_ecommerce/services/storage_service.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';

/// Payment Status Screen — Recovery screen for app close/crash during payment
class PaymentStatusScreen extends StatefulWidget {
  final String? reservationCode;
  final String? orderGroupId;
  final bool isPickup;

  const PaymentStatusScreen({
    super.key,
    this.reservationCode,
    this.orderGroupId,
    this.isPickup = false,
  });

  @override
  State<PaymentStatusScreen> createState() => _PaymentStatusScreenState();
}

class _PaymentStatusScreenState extends State<PaymentStatusScreen> {
  PaymentStatus _status = PaymentStatus.checking;
  Timer? _pollTimer;
  int _pollAttempts = 0;
  static const int _maxPollAttempts = 10; // 30 seconds (3s intervals)
  Map<String, dynamic>? _orderData;

  @override
  void initState() {
    super.initState();
    _startPolling();
  }

  @override
  void dispose() {
    _pollTimer?.cancel();
    super.dispose();
  }

  void _startPolling() {
    _pollTimer = Timer.periodic(const Duration(seconds: 3), (timer) {
      if (_pollAttempts >= _maxPollAttempts) {
        timer.cancel();
        if (_status == PaymentStatus.checking) {
          setState(() => _status = PaymentStatus.pending);
        }
        return;
      }
      _pollAttempts++;
      _checkPaymentStatus();
    });

    // Also check immediately
    _checkPaymentStatus();
  }

  Future<void> _checkPaymentStatus() async {
    try {
      // [AI] VMarket security: auth token ONLY from flutter_secure_storage via StorageService.
      final token = di.sl<StorageService>().getString(AppConstants.userLoginToken);

      if (token == null || token.isEmpty) {
        _pollTimer?.cancel();
        setState(() => _status = PaymentStatus.failed);
        return;
      }

      if (widget.isPickup && widget.reservationCode != null) {
        // Check pickup reservation status
        final response = await http.get(
          Uri.parse('${AppConstants.baseUrl}/api/v1/customer/pickup-reservations/${widget.reservationCode}'),
          headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer $token',
          },
        );

        if (response.statusCode == 200) {
          final data = json.decode(response.body);
          if (data['status'] == true && data['reservation'] != null) {
            final reservation = data['reservation'];
            if (reservation['order_id'] != null) {
              // Payment successful - order created.
              // The OTP (pickup_verification_code) is NOT exposed on the reservation
              // show endpoint; it lives only on the Order. Fetch it authoritatively.
              String? pickupVerificationCode;
              try {
                final orderResponse = await http.get(
                  Uri.parse('${AppConstants.baseUrl}${AppConstants.getOrderFromOrderId}${reservation['order_id']}'),
                  headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer $token',
                  },
                );
                if (orderResponse.statusCode == 200) {
                  final orderData = json.decode(orderResponse.body);
                  pickupVerificationCode = orderData['pickup_verification_code'];
                }
              } catch (e) {
                debugPrint('Pickup order OTP fetch error: $e');
              }

              _pollTimer?.cancel();
              setState(() {
                _status = PaymentStatus.success;
                _orderData = {
                  'order_id': reservation['order_id'],
                  'pickup_verification_code': pickupVerificationCode ?? 'XXXXXX',
                  'cashback_earned': reservation['cashback_earned'],
                  'shop_name': reservation['shop']?['name'] ?? 'Victorious Store',
                  'shop_address': reservation['shop']?['address'],
                };
              });
            }
          }
        }
      } else if (widget.orderGroupId != null) {
        // Check delivery order status
        final response = await http.get(
          Uri.parse('${AppConstants.baseUrl}/api/v1/customer/order/list?limit=5&offset=0'),
          headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer $token',
          },
        );

        if (response.statusCode == 200) {
          final data = json.decode(response.body);
          if (data['orders'] != null) {
            final orders = data['orders'] as List;
            // Look for order with matching order_group_id
            final matchingOrder = orders.firstWhere(
              (order) => order['order_group_id'] == widget.orderGroupId,
              orElse: () => null,
            );

            if (matchingOrder != null) {
              // Order found - payment successful
              _pollTimer?.cancel();
              setState(() {
                _status = PaymentStatus.success;
                _orderData = {
                  'order_id': matchingOrder['id'],
                };
              });
            }
          }
        }
      }
    } catch (e) {
      // Continue polling on error
      debugPrint('Payment status check error: $e');
    }
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: _status == PaymentStatus.failed,
      onPopInvokedWithResult: (didPop, result) {
        if (!didPop && (_status == PaymentStatus.success || _status == PaymentStatus.pending)) {
          // Prevent back navigation during success/pending
          return;
        }
      },
      child: Scaffold(
        appBar: CustomAppBar(
          title: getTranslated('payment_status', context) ?? 'Payment Status',
          isBackButtonExist: _status == PaymentStatus.failed,
        ),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.all(Dimensions.paddingSizeLarge),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                _buildStatusIcon(),
                const SizedBox(height: Dimensions.paddingSizeLarge),
                _buildStatusTitle(),
                const SizedBox(height: Dimensions.paddingSizeDefault),
                _buildStatusMessage(),
                const SizedBox(height: Dimensions.paddingSizeExtraLarge),
                _buildActionButtons(),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildStatusIcon() {
    switch (_status) {
      case PaymentStatus.checking:
        return SizedBox(
          width: 80,
          height: 80,
          child: CircularProgressIndicator(
            strokeWidth: 6,
            color: Theme.of(context).primaryColor,
          ),
        );
      case PaymentStatus.success:
        return Container(
          width: 80,
          height: 80,
          decoration: BoxDecoration(
            color: const Color(0xFF10B981).withValues(alpha: 0.12),
            shape: BoxShape.circle,
          ),
          child: const Icon(
            Icons.check_circle_rounded,
            color: Color(0xFF10B981),
            size: 56,
          ),
        );
      case PaymentStatus.pending:
        return Container(
          width: 80,
          height: 80,
          decoration: BoxDecoration(
            color: const Color(0xFFF59E0B).withValues(alpha: 0.12),
            shape: BoxShape.circle,
          ),
          child: const Icon(
            Icons.schedule_rounded,
            color: Color(0xFFF59E0B),
            size: 56,
          ),
        );
      case PaymentStatus.failed:
        return Container(
          width: 80,
          height: 80,
          decoration: BoxDecoration(
            color: const Color(0xFFEF4444).withValues(alpha: 0.12),
            shape: BoxShape.circle,
          ),
          child: const Icon(
            Icons.cancel_rounded,
            color: Color(0xFFEF4444),
            size: 56,
          ),
        );
    }
  }

  Widget _buildStatusTitle() {
    String title;
    switch (_status) {
      case PaymentStatus.checking:
        title = getTranslated('payment_status_checking', context) ?? 'Checking Payment Status';
        break;
      case PaymentStatus.success:
        title = getTranslated('payment_done', context) ?? 'Payment Successful';
        break;
      case PaymentStatus.pending:
        title = getTranslated('payment_status_pending', context) ?? 'Payment Pending';
        break;
      case PaymentStatus.failed:
        title = getTranslated('payment_failed', context) ?? 'Payment Not Completed';
        break;
    }

    return Text(
      title,
      style: titilliumBold.copyWith(
        fontSize: Dimensions.fontSizeExtraLarge,
        color: Theme.of(context).textTheme.bodyLarge?.color,
      ),
      textAlign: TextAlign.center,
    );
  }

  Widget _buildStatusMessage() {
    String message;
    switch (_status) {
      case PaymentStatus.checking:
        message = 'Please wait while we verify your payment...';
        break;
      case PaymentStatus.success:
        message = widget.isPickup
            ? 'Your pickup order is ready. Check your handover OTP below.'
            : 'Your order has been placed successfully.';
        break;
      case PaymentStatus.pending:
        message = getTranslated('payment_status_pending', context) ??
            'Your payment is being verified. We\'ll notify you once confirmed.';
        break;
      case PaymentStatus.failed:
        message = getTranslated('payment_status_failed', context) ??
            'Payment was not completed. Your cart items are unchanged.';
        break;
    }

    return Text(
      message,
      style: titilliumRegular.copyWith(
        fontSize: Dimensions.fontSizeDefault,
        color: Theme.of(context).hintColor,
        height: 1.5,
      ),
      textAlign: TextAlign.center,
    );
  }

  Widget _buildActionButtons() {
    switch (_status) {
      case PaymentStatus.checking:
        return const SizedBox.shrink(); // No buttons while checking

      case PaymentStatus.success:
        if (widget.isPickup && _orderData != null) {
          return ElevatedButton(
            onPressed: () {
              Navigator.of(context).pushAndRemoveUntil(
                MaterialPageRoute(
                  builder: (_) => PickupOrderSuccessScreen(
                    orderId: _orderData!['order_id'],
                    pickupVerificationCode: _orderData!['pickup_verification_code'] ?? 'XXXXXX',
                    cashbackEarned: _orderData!['cashback_earned']?.toDouble(),
                    shopName: _orderData!['shop_name'],
                    shopAddress: _orderData!['shop_address'],
                  ),
                ),
                (route) => false,
              );
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: Theme.of(context).primaryColor,
              padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 16),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(8),
              ),
            ),
            child: Text(
              getTranslated('view_order', context) ?? 'View Order Details',
              style: titilliumSemiBold.copyWith(
                fontSize: Dimensions.fontSizeDefault,
                color: Colors.white,
              ),
            ),
          );
        } else {
          return ElevatedButton(
            onPressed: () {
              Navigator.of(context).pushAndRemoveUntil(
                MaterialPageRoute(builder: (_) => const DashBoardScreen(pageIndex: 0)),
                (route) => false,
              );
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: Theme.of(context).primaryColor,
              padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 16),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(8),
              ),
            ),
            child: Text(
              getTranslated('continue_shopping', context) ?? 'Continue Shopping',
              style: titilliumSemiBold.copyWith(
                fontSize: Dimensions.fontSizeDefault,
                color: Colors.white,
              ),
            ),
          );
        }

      case PaymentStatus.pending:
        return Column(
          children: [
            ElevatedButton.icon(
              onPressed: () {
                setState(() {
                  _status = PaymentStatus.checking;
                  _pollAttempts = 0;
                });
                _startPolling();
              },
              icon: const Icon(Icons.refresh),
              label: Text(getTranslated('check_again', context) ?? 'Check Again'),
              style: ElevatedButton.styleFrom(
                backgroundColor: Theme.of(context).primaryColor,
                padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 16),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
            ),
            const SizedBox(height: Dimensions.paddingSizeDefault),
            TextButton(
              onPressed: () {
                Navigator.of(context).pushAndRemoveUntil(
                  MaterialPageRoute(builder: (_) => const DashBoardScreen(pageIndex: 0)),
                  (route) => false,
                );
              },
              child: Text(
                getTranslated('back_to_home', context) ?? 'Back to Home',
                style: titilliumSemiBold.copyWith(
                  fontSize: Dimensions.fontSizeDefault,
                  color: Theme.of(context).primaryColor,
                ),
              ),
            ),
          ],
        );

      case PaymentStatus.failed:
        return ElevatedButton(
          onPressed: () {
            Navigator.of(context).pop();
          },
          style: ElevatedButton.styleFrom(
            backgroundColor: Theme.of(context).primaryColor,
            padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 16),
            shape: RoundedRectangleBorder(
              borderRadius: BorderRadius.circular(8),
            ),
          ),
          child: Text(
            getTranslated('back', context) ?? 'Go Back',
            style: titilliumSemiBold.copyWith(
              fontSize: Dimensions.fontSizeDefault,
              color: Colors.white,
            ),
          ),
        );
    }
  }
}

enum PaymentStatus {
  checking,
  success,
  pending,
  failed,
}
