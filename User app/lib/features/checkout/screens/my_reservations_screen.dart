import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_app_bar_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/checkout/domain/models/pickup_reservation_model.dart';
import 'package:flutter_sixvalley_ecommerce/helper/price_converter.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/utill/app_constants.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';

/// My Pickup Reservations — List active and historical reservations
class MyReservationsScreen extends StatefulWidget {
  const MyReservationsScreen({super.key});

  @override
  State<MyReservationsScreen> createState() => _MyReservationsScreenState();
}

class _MyReservationsScreenState extends State<MyReservationsScreen> with SingleTickerProviderStateMixin {
  List<PickupReservationModel> _activeReservations = [];
  List<PickupReservationModel> _historyReservations = [];
  bool _isLoading = true;
  String? _errorMessage;
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _fetchReservations();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _fetchReservations() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final prefs = await SharedPreferences.getInstance();
      final token = prefs.getString(AppConstants.token);

      if (token == null || token.isEmpty) {
        setState(() {
          _isLoading = false;
          _errorMessage = 'Please log in to view your reservations';
        });
        return;
      }

      final response = await http.get(
        Uri.parse('${AppConstants.baseUrl}${AppConstants.pickupReservationsUri}'),
        headers: {
          'Content-Type': 'application/json',
          'Authorization': 'Bearer $token',
        },
      );

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['status'] == true) {
          final List reservationsList = data['reservations'] ?? [];
          final List<PickupReservationModel> allReservations = reservationsList
              .map((json) => PickupReservationModel.fromJson(json))
              .toList();

          setState(() {
            _activeReservations = allReservations
                .where((r) =>
                    r.status == 'pending_inspection' ||
                    r.status == 'inspected_accepted')
                .toList();
            _historyReservations = allReservations
                .where((r) =>
                    r.status == 'expired' ||
                    r.status == 'inspected_rejected' ||
                    r.status == 'order_placed')
                .toList();
            _isLoading = false;
          });
        } else {
          setState(() {
            _isLoading = false;
            _errorMessage = data['message'] ?? 'Failed to load reservations';
          });
        }
      } else if (response.statusCode == 401) {
        setState(() {
          _isLoading = false;
          _errorMessage = 'Session expired. Please log in again';
        });
      } else {
        setState(() {
          _isLoading = false;
          _errorMessage = 'Failed to load reservations';
        });
      }
    } catch (e) {
      setState(() {
        _isLoading = false;
        _errorMessage = 'Network error. Please try again';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: CustomAppBar(
        title: getTranslated('my_pickup_reservations', context) ?? 'My Pickup Reservations',
        isBackButtonExist: true,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _errorMessage != null
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(Dimensions.paddingSizeLarge),
                    child: Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Icon(
                          Icons.error_outline,
                          size: 64,
                          color: Theme.of(context).hintColor,
                        ),
                        const SizedBox(height: Dimensions.paddingSizeDefault),
                        Text(
                          _errorMessage!,
                          textAlign: TextAlign.center,
                          style: titilliumRegular.copyWith(
                            fontSize: Dimensions.fontSizeDefault,
                            color: Theme.of(context).hintColor,
                          ),
                        ),
                        const SizedBox(height: Dimensions.paddingSizeLarge),
                        ElevatedButton.icon(
                          onPressed: _fetchReservations,
                          icon: const Icon(Icons.refresh),
                          label: Text(getTranslated('RETRY', context) ?? 'Retry'),
                        ),
                      ],
                    ),
                  ),
                )
              : _activeReservations.isEmpty && _historyReservations.isEmpty
                  ? Center(
                      child: Padding(
                        padding: const EdgeInsets.all(Dimensions.paddingSizeLarge),
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              Icons.storefront_outlined,
                              size: 80,
                              color: Theme.of(context).hintColor.withValues(alpha: 0.5),
                            ),
                            const SizedBox(height: Dimensions.paddingSizeDefault),
                            Text(
                              getTranslated('no_reservation_found', context) ?? 'No reservations found',
                              style: titilliumSemiBold.copyWith(
                                fontSize: Dimensions.fontSizeLarge,
                                color: Theme.of(context).hintColor,
                              ),
                            ),
                            const SizedBox(height: Dimensions.paddingSizeSmall),
                            Text(
                              'Create a pickup reservation from checkout',
                              textAlign: TextAlign.center,
                              style: titilliumRegular.copyWith(
                                fontSize: Dimensions.fontSizeDefault,
                                color: Theme.of(context).hintColor,
                              ),
                            ),
                          ],
                        ),
                      ),
                    )
                  : Column(
                      children: [
                        Container(
                          margin: const EdgeInsets.symmetric(
                            horizontal: Dimensions.paddingSizeDefault,
                            vertical: Dimensions.paddingSizeSmall,
                          ),
                          decoration: BoxDecoration(
                            color: Theme.of(context).primaryColor.withValues(alpha: 0.08),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: TabBar(
                            controller: _tabController,
                            indicator: BoxDecoration(
                              color: Theme.of(context).primaryColor,
                              borderRadius: BorderRadius.circular(8),
                            ),
                            labelColor: Colors.white,
                            unselectedLabelColor: Theme.of(context).textTheme.bodyLarge?.color,
                            labelStyle: titilliumSemiBold.copyWith(fontSize: Dimensions.fontSizeDefault),
                            unselectedLabelStyle: titilliumRegular.copyWith(fontSize: Dimensions.fontSizeDefault),
                            tabs: [
                              Tab(
                                text: '${getTranslated('active', context) ?? 'Active'} (${_activeReservations.length})',
                              ),
                              Tab(
                                text: '${getTranslated('history', context) ?? 'History'} (${_historyReservations.length})',
                              ),
                            ],
                          ),
                        ),
                        Expanded(
                          child: TabBarView(
                            controller: _tabController,
                            children: [
                              _buildReservationsList(_activeReservations, isActive: true),
                              _buildReservationsList(_historyReservations, isActive: false),
                            ],
                          ),
                        ),
                      ],
                    ),
    );
  }

  Widget _buildReservationsList(List<PickupReservationModel> reservations, {required bool isActive}) {
    if (reservations.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(Dimensions.paddingSizeLarge),
          child: Text(
            isActive
                ? 'No active reservations'
                : 'No reservation history',
            style: titilliumRegular.copyWith(
              fontSize: Dimensions.fontSizeDefault,
              color: Theme.of(context).hintColor,
            ),
          ),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: _fetchReservations,
      child: ListView.builder(
        padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
        itemCount: reservations.length,
        itemBuilder: (context, index) {
          final reservation = reservations[index];
          return _ReservationCard(
            reservation: reservation,
            isActive: isActive,
            onTap: () {
              // Navigate to reservation detail screen
              Navigator.pushNamed(
                context,
                '/reservation-detail',
                arguments: reservation,
              );
            },
          );
        },
      ),
    );
  }
}

class _ReservationCard extends StatelessWidget {
  final PickupReservationModel reservation;
  final bool isActive;
  final VoidCallback? onTap;

  const _ReservationCard({
    required this.reservation,
    required this.isActive,
    this.onTap,
  });

  Color _getStatusColor(BuildContext context) {
    switch (reservation.status) {
      case 'pending_inspection':
        return const Color(0xFFF59E0B); // Amber
      case 'inspected_accepted':
        return const Color(0xFF10B981); // Green
      case 'inspected_rejected':
        return const Color(0xFFEF4444); // Red
      case 'expired':
        return Theme.of(context).hintColor;
      case 'order_placed':
        return const Color(0xFF3B82F6); // Blue
      default:
        return Theme.of(context).hintColor;
    }
  }

  String _getStatusLabel(BuildContext context) {
    switch (reservation.status) {
      case 'pending_inspection':
        return getTranslated('pending_inspection_label', context) ?? 'Awaiting Inspection';
      case 'inspected_accepted':
        return getTranslated('inspected_accepted_label', context) ?? 'Ready to Pay';
      case 'inspected_rejected':
        return getTranslated('inspected_rejected_label', context) ?? 'Rejected';
      case 'expired':
        return 'Expired';
      case 'order_placed':
        return 'Completed';
      default:
        return reservation.status ?? 'Unknown';
    }
  }

  @override
  Widget build(BuildContext context) {
    final statusColor = _getStatusColor(context);
    final statusLabel = _getStatusLabel(context);
    final amount = double.tryParse(reservation.totalAmount ?? '0') ?? 0.0;

    return Container(
      margin: const EdgeInsets.only(bottom: Dimensions.paddingSizeDefault),
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
        border: Border.all(
          color: isActive
              ? Theme.of(context).primaryColor.withValues(alpha: 0.15)
              : Theme.of(context).hintColor.withValues(alpha: 0.1),
        ),
      ),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Header Row
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Row(
                      children: [
                        Icon(
                          isActive ? Icons.access_time_filled : Icons.history_rounded,
                          color: statusColor,
                          size: 20,
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            reservation.reservationCode ?? 'RES-XXXXXX',
                            style: titilliumBold.copyWith(
                              fontSize: Dimensions.fontSizeDefault,
                              color: Theme.of(context).textTheme.bodyLarge?.color,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: statusColor.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      statusLabel,
                      style: titilliumSemiBold.copyWith(
                        fontSize: Dimensions.fontSizeSmall,
                        color: statusColor,
                      ),
                    ),
                  ),
                ],
              ),

              const SizedBox(height: Dimensions.paddingSizeSmall),

              // Shop Name
              Row(
                children: [
                  Icon(
                    Icons.storefront_rounded,
                    size: 16,
                    color: Theme.of(context).hintColor,
                  ),
                  const SizedBox(width: 6),
                  Expanded(
                    child: Text(
                      reservation.shop?.name ?? 'Victorious Store',
                      style: titilliumRegular.copyWith(
                        fontSize: Dimensions.fontSizeSmall,
                        color: Theme.of(context).hintColor,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 6),

              // Amount
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    getTranslated('total', context) ?? 'Total',
                    style: titilliumRegular.copyWith(
                      fontSize: Dimensions.fontSizeSmall,
                      color: Theme.of(context).hintColor,
                    ),
                  ),
                  Text(
                    PriceConverter.convertPrice(context, amount),
                    style: titilliumBold.copyWith(
                      fontSize: Dimensions.fontSizeDefault,
                      color: Theme.of(context).primaryColor,
                    ),
                  ),
                ],
              ),

              // Show Pay Now button for inspected_accepted status
              if (reservation.status == 'inspected_accepted') ...[
                const SizedBox(height: Dimensions.paddingSizeSmall),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: onTap,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF10B981),
                      padding: const EdgeInsets.symmetric(vertical: 10),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(8),
                      ),
                    ),
                    child: Text(
                      getTranslated('pay_now', context) ?? 'Pay Now',
                      style: titilliumSemiBold.copyWith(
                        fontSize: Dimensions.fontSizeDefault,
                        color: Colors.white,
                      ),
                    ),
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}
