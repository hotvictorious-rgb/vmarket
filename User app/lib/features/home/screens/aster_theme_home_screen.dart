import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/title_row_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/auth/controllers/auth_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/banner/controllers/banner_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/banner/widgets/banners_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/banner/widgets/footer_banner_slider_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/banner/widgets/single_banner_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/brand/controllers/brand_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/cart/controllers/cart_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/category/controllers/category_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/category/widgets/category_list_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/clearance_sale/widgets/clearance_sale_list_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/deal/controllers/featured_deal_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/deal/controllers/flash_deal_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/deal/widgets/featured_deal_list_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/deal/widgets/flash_deals_list_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/home/shimmers/flash_deal_shimmer.dart';
import 'package:flutter_sixvalley_ecommerce/features/home/shimmers/order_again_shimmer.dart';
import 'package:flutter_sixvalley_ecommerce/features/home/shimmers/top_store_shimmer.dart';
import 'package:flutter_sixvalley_ecommerce/features/home/widgets/announcement_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/home/widgets/aster_theme/find_what_you_need_shimmer.dart';
import 'package:flutter_sixvalley_ecommerce/features/home/widgets/aster_theme/find_what_you_need_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/home/widgets/aster_theme/more_store_list_view_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/home/widgets/aster_theme/order_again_list_view_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/home/widgets/featured_product_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/home/widgets/just_for_you/just_for_you_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/home/widgets/product_list_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/home/widgets/product_type_popup_menu_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/home/widgets/search_home_page_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/notification/controllers/notification_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/order/controllers/order_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/product/controllers/product_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/product/controllers/seller_product_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/product/domain/models/product_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/product/enums/product_type.dart';
import 'package:flutter_sixvalley_ecommerce/features/product/widgets/home_category_product_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/product/widgets/latest_product_list_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/product/widgets/recommended_product_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/profile/controllers/profile_contrroller.dart';
import 'package:flutter_sixvalley_ecommerce/features/shop/controllers/shop_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/shop/widgets/more_store_list_view.dart';
import 'package:flutter_sixvalley_ecommerce/features/home/widgets/top_seller_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/splash/controllers/splash_controller.dart';
import 'package:flutter_sixvalley_ecommerce/helper/responsive_helper.dart';
import 'package:flutter_sixvalley_ecommerce/helper/route_healper.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/main.dart';
import 'package:flutter_sixvalley_ecommerce/theme/controllers/theme_controller.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';

import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';


class AsterThemeHomeScreen extends StatefulWidget {
  const AsterThemeHomeScreen({super.key});

  @override
  State<AsterThemeHomeScreen> createState() => _AsterThemeHomeScreenState();

  static Future<void> loadData(bool reload) async {
    final context = Get.context;
    if (context == null) return;

    final shopController = Provider.of<ShopController>(context, listen: false);
    final categoryController = Provider.of<CategoryController>(context, listen: false);
    final bannerController = Provider.of<BannerController>(context, listen: false);
    final productController = Provider.of<ProductController>(context, listen: false);
    final brandController = Provider.of<BrandController>(context, listen: false);
    final featuredDealController = Provider.of<FeaturedDealController>(context, listen: false);
    final notificationController = Provider.of<NotificationController>(context, listen: false);
    final cartController = Provider.of<CartController>(context, listen: false);
    final profileController = Provider.of<ProfileController>(context, listen: false);
    final splashController = Provider.of<SplashController>(context, listen: false);
    final sellerProductController = Provider.of<SellerProductController>(context, listen: false);
    final orderController = Provider.of<OrderController>(context, listen: false);

    splashController.initConfig(context, null, null);

    // Primary Visual Fold
    await Future.wait([
      categoryController.getCategoryList(reload).catchError((e) => debugPrint('Error loading categories: $e')),
      bannerController.getBannerList().catchError((e) => debugPrint('Error loading banners: $e')),
      productController.getLatestProductList(1, isUpdate: false).catchError((e) => debugPrint('Error loading latest products: $e')),
      productController.getFeaturedProductModel(1, isUpdate: reload).catchError((e) => debugPrint('Error loading featured products: $e')),
    ]);

    // Secondary Visual Fold
    await Future.delayed(const Duration(milliseconds: 100));
    await Future.wait([
      productController.getHomeCategoryProductList(reload).catchError((e) => debugPrint('Error: $e')),
      shopController.getTopSellerList(offset: 1, isUpdate: reload).catchError((e) => debugPrint('Error: $e')),
      brandController.getBrandList(offset: 1, isUpdate: reload).catchError((e) => debugPrint('Error: $e')),
      featuredDealController.getFeaturedDealList().catchError((e) => debugPrint('Error: $e')),
      productController.getRecommendedProduct().catchError((e) => debugPrint('Error: $e')),
      productController.getJustForYouProduct(1, isUpdate: reload).catchError((e) => debugPrint('Error: $e')),
      productController.getClearanceAllProductList(1, isUpdate: reload).catchError((e) => debugPrint('Error: $e')),
      shopController.getMoreStore().catchError((e) => debugPrint('Error: $e')),
    ]);

    // Background Utilities
    await Future.delayed(const Duration(milliseconds: 100));
    try {
      if (!context.mounted) return;
      await cartController.getCartData(context);
      if (notificationController.notificationModel == null || reload) {
        await notificationController.getNotificationList(1);
      }
      if (!context.mounted) return;
      if (Provider.of<AuthController>(context, listen: false).isLoggedIn()) {
        if (profileController.userInfoModel == null) {
          await profileController.getUserInfo(context);
        }
        await sellerProductController.getShopAgainFromRecentStore();
        if (orderController.orderModel == null || (orderController.orderModel != null && orderController.orderModel!.orders!.isEmpty) || reload) {
          await orderController.getOrderList(1, 'delivered', type: 'reorder');
        }
      }
    } catch (e) {
      debugPrint('Aster background error: $e');
    }
  }
}

class _AsterThemeHomeScreenState extends State<AsterThemeHomeScreen> {
  final ScrollController _scrollController = ScrollController();



  void passData(int index, String title) {
    index = index;
    title = title;
  }

  bool singleVendor = false;
  @override
  void initState() {
    super.initState();

    singleVendor = Provider.of<SplashController>(context, listen: false).configModel?.businessMode == "single";

  }


  @override
  Widget build(BuildContext context) {

    return Scaffold(resizeToAvoidBottomInset: false,
      body: SafeArea(child: RefreshIndicator(
          onRefresh: () async {
            await AsterThemeHomeScreen.loadData(true);
          },
        child: CustomScrollView(controller: _scrollController, slivers: [
          SliverAppBar(
            floating: true,
            elevation: 0,
            centerTitle: false,
            automaticallyImplyLeading: false,
            backgroundColor: Theme.of(context).primaryColor,
            title: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              crossAxisAlignment: CrossAxisAlignment.center,
              children: [
                // Premium Two-Tone Brand Wordmark
                Expanded(
                  flex: 5,
                  child: Align(
                    alignment: Alignment.centerLeft,
                    child: ShaderMask(
                      blendMode: BlendMode.srcIn,
                      shaderCallback: (bounds) => const LinearGradient(
                        colors: [Color(0xFFFFD700), Color(0xFFFFB300)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ).createShader(bounds),
                      child: RichText(
                        text: TextSpan(
                          children: [
                            TextSpan(
                              text: 'Victorious',
                              style: TextStyle(
                                fontFamily: 'Ubuntu',
                                fontWeight: FontWeight.w900,
                                fontSize: 20,
                                letterSpacing: 0.5,
                                height: 1.1,
                                color: Colors.white,
                                shadows: [Shadow(color: Colors.black.withValues(alpha: 0.35), offset: const Offset(0, 2), blurRadius: 6)],
                              ),
                            ),
                            const TextSpan(text: '\n'),
                            WidgetSpan(
                              child: ShaderMask(
                                blendMode: BlendMode.srcIn,
                                shaderCallback: (_) => const LinearGradient(colors: [Colors.white, Color(0xFFF0F0F0)]).createShader(const Rect.fromLTWH(0, 0, 120, 20)),
                                child: Text(
                                  'MARKET',
                                  style: TextStyle(
                                    fontFamily: 'Ubuntu',
                                    fontWeight: FontWeight.w900,
                                    fontSize: 18,
                                    letterSpacing: 4.5,
                                    height: 1.0,
                                    color: Colors.white,
                                    shadows: [Shadow(color: Colors.black.withValues(alpha: 0.3), offset: const Offset(0, 2), blurRadius: 5)],
                                  ),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
                // Call to Order + Notification Bell
                Expanded(
                  flex: 6,
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.end,
                    crossAxisAlignment: CrossAxisAlignment.center,
                    children: [
                      if (Provider.of<SplashController>(context, listen: false).configModel?.companyPhone != null &&
                          Provider.of<SplashController>(context, listen: false).configModel!.companyPhone!.isNotEmpty)
                        InkWell(
                          onTap: () async {
                            final Uri launchUri = Uri(scheme: 'tel', path: Provider.of<SplashController>(context, listen: false).configModel!.companyPhone);
                            if (await canLaunchUrl(launchUri)) await launchUrl(launchUri);
                          },
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            crossAxisAlignment: CrossAxisAlignment.end,
                            children: [
                              Text('CALL TO ORDER:', style: textBold.copyWith(color: const Color(0xFFFFD700), fontSize: 9, letterSpacing: 0.5)),
                              const SizedBox(height: 1),
                              Text(Provider.of<SplashController>(context, listen: false).configModel!.companyPhone!, style: textBold.copyWith(color: Colors.white, fontSize: 11, letterSpacing: 0.3)),
                            ],
                          ),
                        ),
                      const SizedBox(width: 10),
                      Consumer<NotificationController>(
                        builder: (context, notificationProvider, _) {
                          final int unreadCount = notificationProvider.getUnreadNotificationCount();
                          return InkWell(
                            onTap: () => RouterHelper.getNotificationRoute(action: RouteAction.push),
                            child: Stack(
                              clipBehavior: Clip.none,
                              alignment: Alignment.center,
                              children: [
                                const Icon(Icons.notifications_none_rounded, color: Colors.white, size: 24),
                                if (unreadCount > 0)
                                  Positioned(
                                    top: -3, right: -4,
                                    child: Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
                                      decoration: const BoxDecoration(color: Color(0xFFFFD700), shape: BoxShape.circle),
                                      constraints: const BoxConstraints(minWidth: 14, minHeight: 14),
                                      child: Center(child: Text(unreadCount > 99 ? '99+' : '$unreadCount', style: textBold.copyWith(color: const Color(0xFF4A148C), fontSize: 8, height: 1.0))),
                                    ),
                                  ),
                              ],
                            ),
                          );
                        },
                      ),
                    ],
                  ),
                ),
              ],
            )),

          SliverToBoxAdapter(child: Provider.of<SplashController>(context, listen: false).configModel!.announcement!.status == '1'?
          Consumer<SplashController>(
            builder: (context, announcement, _){
              return (announcement.configModel!.announcement!.announcement != null && announcement.onOff)?
              AnnouncementWidget(announcement: announcement.configModel!.announcement):const SizedBox();
            },
          ) : const SizedBox()),

          // Search Button
          SliverPersistentHeader(pinned: true, delegate: SliverDelegate(
            child: InkWell(
              onTap: ()=> RouterHelper.getSearchRoute(action: RouteAction.push),
              child: const Hero(tag: 'search', child: Material(child: SearchHomePageWidget())),
            ),
          )),


          SliverToBoxAdapter(child: const BannersWidget()),
          // SliverToBoxAdapter(child: SizedBox(height: Dimensions.paddingSizeDefault)),

          SliverToBoxAdapter(child: const CategoryListWidget(isHomePage: true)),
          SliverToBoxAdapter(child: SizedBox(height: Dimensions.paddingSizeDefault)),

          SliverToBoxAdapter(
            child: Consumer<FlashDealController>(
              builder: (context, megaDeal, child) {
                  return  megaDeal.flashDeal != null ? megaDeal.flashDealList.isNotEmpty ?
                  Column(children: [
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
                      child: FlashDealBar(
                        title: getTranslated('flash_deal', context)!.toUpperCase(),
                        eventDuration: megaDeal.flashDeal != null ? megaDeal.duration : null,
                        onTap: () {
                          RouterHelper.getFlashDealScreenViewRoute();
                        },
                      )
                    ),
                    const SizedBox(height: Dimensions.paddingSizeSmall),

                    Padding( padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
                      child: Text(getTranslated('hurry_up_the_offer_is_limited_grab_while_it_lasts', context)??'',
                          textAlign: TextAlign.center,
                          style: textRegular.copyWith(color: Provider.of<ThemeController>(context, listen: false).darkTheme?
                          Theme.of(context).hintColor : Theme.of(context).primaryColor, fontSize: Dimensions.fontSizeDefault)),
                    ),
                    const SizedBox(height: Dimensions.paddingSizeDefault),

                    const Padding(
                      padding: EdgeInsets.only(bottom: Dimensions.paddingSizeDefault),
                      child: FlashDealsListWidget(),
                    ),
                  ]) : const SizedBox.shrink(): const FlashDealShimmer();
                }
            ),
          ),


          SliverToBoxAdapter(
            child: Consumer<ProductController>(
              builder: (context, productController, _) {
                return productController.findWhatYouNeedModel != null ?
                (productController.findWhatYouNeedModel!.findWhatYouNeed != null &&
                    productController.findWhatYouNeedModel!.findWhatYouNeed!.isNotEmpty)?
                Column(children: [
                  TitleRowWidget(title: getTranslated('find_what_you_need', context)),
                  const SizedBox(height: Dimensions.paddingSizeSmall),

                  SizedBox(height: ResponsiveHelper.isTab(context)?  165 :150, child: const FindWhatYouNeedView()),
                  const SizedBox(height: Dimensions.paddingSizeDefault),
                ]): const SizedBox() : const FindWhatYouNeedShimmer();
              }
            ),
          ),




          SliverToBoxAdapter(
            child:  (Provider.of<AuthController>(context, listen: false).isLoggedIn()) ?
            Consumer<OrderController>(
              builder: (context, orderProvider,_) {
                return orderProvider.deliveredOrderModel != null ?
                (orderProvider.deliveredOrderModel!.orders != null && orderProvider.deliveredOrderModel!.orders!.isNotEmpty) ?
                const Padding(
                  padding: EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
                  child: OrderAgainView(),
                ) :
                Consumer<BannerController>(builder: (context, bannerProvider, child) {
                  return bannerProvider.sideBarBanner != null ?
                    Padding(
                      padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
                      child: SingleBannersWidget(bannerModel : bannerProvider.sideBarBanner,
                        height: MediaQuery.of(context).size.width * 1.2),
                    ) : const SizedBox();
                  }
                ) :
                  const OrderAgainShimmerShimmer();
              }
            ) : Consumer<BannerController>(builder: (context, bannerProvider, child) {
              return bannerProvider.sideBarBanner != null?
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
                child: SingleBannersWidget(bannerModel : bannerProvider.sideBarBanner,
                    height: MediaQuery.of(context).size.width * 1.2),
              ):const SizedBox();}),
          ),
          SliverToBoxAdapter(child: SizedBox(height: Dimensions.paddingSizeDefault)),


          if(!singleVendor)
          SliverToBoxAdapter(
            child: Consumer<ShopController>(
              builder: (context, shopController,_) {
                return shopController.topSellerModel != null? (shopController.topSellerModel!.sellers!=null && shopController.topSellerModel!.sellers!.isNotEmpty) ?
                Column(children: [
                  TitleRowWidget(title: getTranslated('top_stores', context),
                      onTap: ()=> RouterHelper.getAllTopSellerRoute(action: RouteAction.push, title: 'top_stores')
                  ),

                  const SizedBox(height: Dimensions.paddingSizeSmall),

                  SizedBox(height: ResponsiveHelper.isTab(context)? 180 : 165, child: const TopSellerWidget()),
                  const SizedBox(height: Dimensions.paddingSizeDefault),

                ]): const SizedBox(): const TopStoreShimmer();
              }
            ),
          ),


            SliverToBoxAdapter(
              child: Consumer<FeaturedDealController>(
                builder: (context, featuredDealProvider, child) {
                  return featuredDealProvider.featuredDealProductList != null?
                  featuredDealProvider.featuredDealProductList!.isNotEmpty ?
                  Stack(children: [
                    Container(width: MediaQuery.of(context).size.width,height: 150,
                        color: Provider.of<ThemeController>(context, listen: false).darkTheme?
                        Theme.of(context).primaryColor.withValues(alpha:.20):Theme.of(context).primaryColor.withValues(alpha:.125)),

                    Padding(padding: const EdgeInsets.only(bottom: Dimensions.paddingSizeDefault),
                        child: Column(children: [
                          Padding(padding: const EdgeInsets.fromLTRB(0, Dimensions.paddingSizeDefault,0 ,
                              Dimensions.paddingSizeDefault),
                              child: TitleRowWidget(title: '${getTranslated('featured_deals', context)}',
                                  onTap: () {
                                    RouterHelper.getFeaturedDealScreenViewRoute();
                                  }
                              )
                          ),
                          const FeaturedDealsListWidget()
                        ]))
                  ]
                  ) :
                  const SizedBox.shrink() : const FindWhatYouNeedShimmer();
                },
              ),
            ),


            SliverToBoxAdapter(
              child: const ClearanceListWidget(),
            ),
            SliverToBoxAdapter(child: SizedBox(height: Dimensions.paddingSizeDefault)),


            SliverToBoxAdapter(
              child: const FooterBannerSliderWidget(),
            ),
            SliverToBoxAdapter(child: SizedBox(height: Dimensions.paddingSizeDefault)),


            SliverToBoxAdapter(
              child: const FeaturedProductWidget(),
            ),
            SliverToBoxAdapter(child: SizedBox(height: Dimensions.paddingSizeDefault)),


            SliverToBoxAdapter(
              child:  Consumer<BannerController>(builder: (context, bannerProvider, child){
                return bannerProvider.topSideBarBannerBottom != null?
                Padding(padding: const EdgeInsets.only(bottom: Dimensions.paddingSizeDefault,
                  left:Dimensions.bannerPadding, right: Dimensions.bannerPadding),
                  child: SingleBannersWidget(
                    bannerModel : bannerProvider.topSideBarBannerBottom,
                    height: MediaQuery.of(context).size.width * 1.2
                  )
                ) : const SizedBox();

              }),
            ),
            SliverToBoxAdapter(child: SizedBox(height: Dimensions.paddingSizeDefault)),


          SliverToBoxAdapter(
            child: Padding(
              padding: EdgeInsets.only(bottom: Dimensions.paddingSizeDefault),
              child: RecommendedProductWidget(fromAsterTheme: true)
            )
          ),


          SliverToBoxAdapter(
            child: const LatestProductListWidget()
          ),
          SliverToBoxAdapter(child: const SizedBox(height: Dimensions.paddingSizeDefault)),


          SliverToBoxAdapter(
            child: Consumer<BannerController>(builder: (context, bannerProvider, child) {
              return bannerProvider.footerBannerList != null && bannerProvider.footerBannerList!.isNotEmpty?
              Padding(padding: const EdgeInsets.only(bottom: Dimensions.paddingSizeDefault,
                left:Dimensions.paddingSizeDefault, right: Dimensions.paddingSizeDefault ),
                child: SingleBannersWidget(bannerModel : bannerProvider.footerBannerList![0],
                  height: MediaQuery.of(context).size.width * 0.5)
              ):const SizedBox();
            }),
          ),
          SliverToBoxAdapter(child: const SizedBox(height: Dimensions.paddingSizeDefault)),


          SliverToBoxAdapter(
            child: Selector<ProductController, ProductModel?>(
              selector: (ctx, productController)=> productController.justForYouProductModel,
              builder: (context, justForYouProductModel,_) {
                return (justForYouProductModel?.products?.isNotEmpty ?? false) ? Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    TitleRowWidget(title: getTranslated('just_for_you', context), onTap: () {
                      RouterHelper.getViewAllProductScreenRoute(productType: ProductType.justForYou, action: RouteAction.push);
                    },),
                    const SizedBox(height: Dimensions.paddingSizeSmall),

                    JustForYouView(productList: justForYouProductModel?.products),
                  ],
                ) : const SizedBox();
              },
            ),
          ),
          SliverToBoxAdapter(child: const SizedBox(height: Dimensions.paddingSizeDefault)),


          SliverToBoxAdapter(
            child: Consumer<ShopController>(
              builder: (context, moreStoreProvider, _) {
                return moreStoreProvider.moreStoreList.isNotEmpty ?
                Column(children: [
                  TitleRowWidget(
                    title: getTranslated('more_store', context),
                    onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => MoreStoreViewListView(title: getTranslated('more_store', context)))),
                  ),
                  const SizedBox(height: Dimensions.paddingSizeSmall),

                  const MoreStoreView(isHome: true),
                  const SizedBox(height: Dimensions.paddingSizeDefault),
                ]):const SizedBox();
              }),
          ),
          SliverToBoxAdapter(child: const SizedBox(height: Dimensions.paddingSizeDefault)),


          const HomeCategoryProductWidget(isHomePage: true),
          SliverToBoxAdapter(child: const SizedBox(height: Dimensions.paddingSizeDefault)),


          SliverToBoxAdapter(
            child:  Consumer<BannerController>(builder: (context, footerBannerProvider, child){
              return footerBannerProvider.mainSectionBanner != null?
              SingleBannersWidget(bannerModel: footerBannerProvider.mainSectionBanner,
                height: MediaQuery.of(context).size.width/4,):const SizedBox();
            }),
          ),

          SliverPersistentHeader(pinned: true, delegate: SliverDelegate(
            child:  Align(
              alignment: Alignment.topLeft,
              child: Container(color: Theme.of(context).scaffoldBackgroundColor, child: const ProductPopupFilterWidget()),
            ),
          )),

          HomeProductListWidget(scrollController: _scrollController),

        ]),
        ),
      ),
    );
  }
}

class SliverDelegate extends SliverPersistentHeaderDelegate {
  Widget child;
  double height;
  SliverDelegate({required this.child, this.height = 70});

  @override
  Widget build(BuildContext context, double shrinkOffset, bool overlapsContent) {
    return child;
  }

  @override
  double get maxExtent => height;

  @override
  double get minExtent => height;

  @override
  bool shouldRebuild(SliverDelegate oldDelegate) {
    return oldDelegate.maxExtent != height || oldDelegate.minExtent != height || child != oldDelegate.child;
  }
}
