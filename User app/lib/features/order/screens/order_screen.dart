import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/features/order/controllers/order_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/order/widgets/order_shimmer_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/order/widgets/order_type_button_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/order/widgets/order_widget.dart';
import 'package:flutter_sixvalley_ecommerce/helper/route_healper.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/main.dart';
import 'package:flutter_sixvalley_ecommerce/features/auth/controllers/auth_controller.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';
import 'package:flutter_sixvalley_ecommerce/utill/images.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/custom_app_bar_widget.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/no_internet_screen_widget.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/not_loggedin_widget.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/paginated_list_view_widget.dart';
import 'package:provider/provider.dart';

class OrderScreen extends StatefulWidget {
  final bool isBacButtonExist;
  final bool fromDashboard;
  final bool fromPlaceOrder;
  const OrderScreen({super.key, this.isBacButtonExist = true, this.fromDashboard = false, this.fromPlaceOrder = false});

  @override
  State<OrderScreen> createState() => _OrderScreenState();
}

class _OrderScreenState extends State<OrderScreen> {
  // FIX 2: One isolated ScrollController per tab to prevent listener bleed.
  final List<ScrollController> _scrollControllers = [
    ScrollController(), // index 0 — Running
    ScrollController(), // index 1 — Delivered
    ScrollController(), // index 2 — Canceled
  ];

  bool isGuestMode = !Provider.of<AuthController>(Get.context!, listen: false).isLoggedIn();

  @override
  void initState() {
    super.initState();
    if (!isGuestMode) {
      Provider.of<OrderController>(context, listen: false).setIndex(0, notify: false);
      Provider.of<OrderController>(context, listen: false).getOrderList(1, 'ongoing');
    }
  }

  @override
  void dispose() {
    // Properly dispose all three controllers to prevent memory leaks.
    for (final controller in _scrollControllers) {
      controller.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: Navigator.canPop(context),
      onPopInvokedWithResult: (didPop, result) async {
        if (widget.fromPlaceOrder) {
          RouterHelper.getDashboardRoute(action: RouteAction.pushReplacement, page: 'home');
        } else {
          return;
        }
      },
      child: RefreshIndicator(
        onRefresh: () async {
          return await Provider.of<OrderController>(context, listen: false).getOrderList(
            1,
            Provider.of<OrderController>(context, listen: false).selectedType,
            refresh: true,
          );
        },
        child: Scaffold(
          appBar: CustomAppBar(
            title: getTranslated('order', context),
            isBackButtonExist: widget.isBacButtonExist,
            onBackPressed: () {
              if (widget.fromPlaceOrder) {
                RouterHelper.getDashboardRoute(action: RouteAction.pushReplacement, page: 'home');
              } else {
                Navigator.of(context).pop();
              }
            },
          ),
          body: isGuestMode
              ? NotLoggedInWidget(
                  message: getTranslated('to_view_the_order_history', context),
                  fromPage: widget.fromDashboard
                      ? '${RouterHelper.dashboardScreen}?page=orders'
                      : RouterHelper.orderScreen,
                )
              : Consumer<OrderController>(
                  builder: (context, orderController, child) {
                    // FIX 3: Use the active tab's dedicated scroll controller.
                    final activeScrollController =
                        _scrollControllers[orderController.orderTypeIndex];

                    // FIX 3: Determine loading state from per-tab flag, not model null-check.
                    final bool showShimmer = orderController.isCurrentTabLoading &&
                        orderController.orderModel == null;

                    return Column(
                      children: [
                        Padding(
                          padding: const EdgeInsets.symmetric(
                            horizontal: Dimensions.paddingSizeLarge,
                            vertical: Dimensions.paddingSizeSmall,
                          ),
                          child: Row(children: [
                            OrderTypeButton(text: getTranslated('RUNNING', context),   index: 0),
                            const SizedBox(width: Dimensions.paddingSizeSmall),
                            OrderTypeButton(text: getTranslated('DELIVERED', context), index: 1),
                            const SizedBox(width: Dimensions.paddingSizeSmall),
                            OrderTypeButton(text: getTranslated('CANCELED', context),  index: 2),
                          ]),
                        ),

                        Expanded(
                          child: showShimmer
                              // Tab is actively loading and has no data yet → shimmer
                              ? const OrderShimmerWidget()
                              : orderController.orderModel != null
                                  ? (orderController.orderModel!.orders != null &&
                                          orderController.orderModel!.orders!.isNotEmpty)
                                      // Has data → show paginated list
                                      ? SingleChildScrollView(
                                          key: ValueKey(
                                              'order_scroll_${orderController.selectedType}'),
                                          controller: activeScrollController,
                                          child: PaginatedListView(
                                            key: ValueKey(
                                                'paginated_orders_${orderController.selectedType}'),
                                            scrollController: activeScrollController,
                                            onPaginate: (int? offset) async {
                                              await orderController.getOrderList(
                                                  offset!, orderController.selectedType);
                                            },
                                            totalSize: orderController.orderModel?.totalSize,
                                            offset: orderController.orderModel?.offset != null
                                                ? int.parse(orderController.orderModel!.offset!)
                                                : 1,
                                            itemView: ListView.builder(
                                              shrinkWrap: true,
                                              physics: const NeverScrollableScrollPhysics(),
                                              itemCount:
                                                  orderController.orderModel?.orders!.length,
                                              padding: const EdgeInsets.all(0),
                                              itemBuilder: (context, index) => OrderWidget(
                                                  orderModel: orderController
                                                      .orderModel?.orders![index]),
                                            ),
                                          ),
                                        )
                                      // Loaded but empty → empty state widget
                                      : const NoInternetOrDataScreenWidget(
                                          isNoInternet: false,
                                          icon: Images.noOrder,
                                          message: 'no_order_found',
                                        )
                                  // Model not yet fetched → show shimmer
                                  : const OrderShimmerWidget(),
                        ),
                      ],
                    );
                  },
                ),
        ),
      ),
    );
  }
}
