import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/features/cashback/controllers/cashback_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/cashback/widgets/cashback_card_widget.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';
import 'package:provider/provider.dart';

class CashbackScreen extends StatefulWidget {
  const CashbackScreen({super.key});

  @override
  State<CashbackScreen> createState() => _CashbackScreenState();
}

class _CashbackScreenState extends State<CashbackScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final controller = Provider.of<CashbackController>(context, listen: false);
      controller.getCashbackSummary(reload: true);
      controller.getCashbackList(1, reload: true);
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        elevation: 0.5,
        backgroundColor: Theme.of(context).cardColor,
        leading: IconButton(
          icon: Icon(Icons.arrow_back_ios, color: Theme.of(context).textTheme.bodyLarge?.color, size: 20),
          onTap: () => Navigator.of(context).pop(),
        ),
        title: Text(
          'Cashback Rewards',
          style: textBold.copyWith(fontSize: Dimensions.fontSizeLarge, color: Theme.of(context).textTheme.bodyLarge?.color),
        ),
      ),
      body: Consumer<CashbackController>(
        builder: (context, cashbackController, _) {
          return RefreshIndicator(
            onRefresh: () async {
              await cashbackController.getCashbackSummary(reload: true);
              await cashbackController.getCashbackList(1, reload: true);
            },
            child: SingleChildScrollView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const CashbackCardWidget(),
                  const SizedBox(height: Dimensions.paddingSizeLarge),

                  Text(
                    'Cashback Activity',
                    style: textBold.copyWith(fontSize: Dimensions.fontSizeLarge),
                  ),
                  const SizedBox(height: Dimensions.paddingSizeSmall),

                  if (cashbackController.isLoading && cashbackController.cashbackList.isEmpty)
                    const Center(child: Padding(
                      padding: EdgeInsets.all(32.0),
                      child: CircularProgressIndicator(),
                    ))
                  else if (cashbackController.cashbackList.isEmpty)
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(Dimensions.paddingSizeLarge),
                      decoration: BoxDecoration(
                        color: Theme.of(context).cardColor,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Theme.of(context).hintColor.withValues(alpha: 0.15)),
                      ),
                      child: Column(
                        children: [
                          const Icon(Icons.receipt_long_outlined, size: 48, color: Colors.grey),
                          const SizedBox(height: 8),
                          Text(
                            'No cashback records yet',
                            style: textBold.copyWith(color: Theme.of(context).hintColor),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'Shop verified items on Victorious MARKET to start earning 5% rewards.',
                            style: textRegular.copyWith(color: Theme.of(context).hintColor, fontSize: Dimensions.fontSizeSmall),
                            textAlign: TextAlign.center,
                          ),
                        ],
                      ),
                    )
                  else
                    ListView.separated(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      itemCount: cashbackController.cashbackList.length,
                      separatorBuilder: (ctx, i) => const SizedBox(height: Dimensions.paddingSizeSmall),
                      itemBuilder: (ctx, i) {
                        final item = cashbackController.cashbackList[i];
                        final isAvailable = item.status == 'available';
                        final isPending = item.status == 'pending';
                        final isRedeemed = item.status == 'redeemed';
                        final Color color = isAvailable ? const Color(0xFF2E7D32) : isPending ? const Color(0xFFF57F17) : isRedeemed ? const Color(0xFF1565C0) : const Color(0xFFC62828);

                        return Container(
                          padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                          decoration: BoxDecoration(
                            color: Theme.of(context).cardColor,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(color: Theme.of(context).hintColor.withValues(alpha: 0.1)),
                          ),
                          child: Row(
                            children: [
                              CircleAvatar(
                                backgroundColor: color.withValues(alpha: 0.15),
                                child: Icon(
                                  isRedeemed ? Icons.shopping_bag_outlined : isPending ? Icons.schedule : Icons.check_circle_outline,
                                  color: color,
                                  size: 20,
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      item.description ?? (item.orderId != null ? 'Order #${item.orderId}' : 'Cashback Reward'),
                                      style: textBold.copyWith(fontSize: Dimensions.fontSizeDefault),
                                    ),
                                    const SizedBox(height: 4),
                                    Text(
                                      'Status: ${item.status?.toUpperCase()}',
                                      style: textRegular.copyWith(fontSize: Dimensions.fontSizeSmall, color: color),
                                    ),
                                  ],
                                ),
                              ),
                              Text(
                                '₦${item.cashbackAmount}',
                                style: textBold.copyWith(fontSize: Dimensions.fontSizeDefault, color: color),
                              ),
                            ],
                          ),
                        );
                      },
                    ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}