import 'package:flutter/material.dart';

import 'package:flutter_sixvalley_ecommerce/common/basewidget/title_row_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/address/controllers/address_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/auth/controllers/auth_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/banner/controllers/banner_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/banner/widgets/banners_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/banner/widgets/footer_banner_slider_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/banner/widgets/single_banner_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/brand/controllers/brand_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/brand/widgets/brand_list_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/cart/controllers/cart_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/category/controllers/category_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/category/widgets/category_list_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/clearance_sale/widgets/clearance_sale_list_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/deal/controllers/featured_deal_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/deal/controllers/flash_deal_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/deal/widgets/featured_deal_list_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/deal/widgets/flash_deals_list_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/home/shimmers/flash_deal_shimmer.dart';
import 'package:flutter_sixvalley_ecommerce/features/home/widgets/announcement_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/home/widgets/aster_theme/find_what_you_need_shimmer.dart';
import 'package:flutter_sixvalley_ecommerce/features/home/widgets/featured_product_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/home/widgets/product_list_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/home/widgets/product_type_popup_menu_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/home/widgets/search_home_page_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/notification/controllers/notification_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/product/controllers/product_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/product/widgets/home_category_product_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/product/widgets/latest_product_list_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/product/widgets/recommended_product_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/profile/controllers/profile_contrroller.dart';
import 'package:flutter_sixvalley_ecommerce/features/shop/controllers/shop_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/home/widgets/top_seller_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/splash/controllers/splash_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/splash/domain/models/config_model.dart';
import 'package:flutter_sixvalley_ecommerce/helper/responsive_helper.dart';
import 'package:flutter_sixvalley_ecommerce/helper/route_healper.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/main.dart';
import 'package:flutter_sixvalley_ecommerce/theme/controllers/theme_controller.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';
import 'package:url_launcher/url_launcher.dart';
import 'package:provider/provider.dart';


class HomePage extends StatefulWidget {
  const HomePage({super.key});

  @override
  State<HomePage> createState() => _HomePageState();

  static Future<void> loadData(bool reload) async {
    final context = Get.context;
    if (context == null) return;

    final shopController = Provider.of<ShopController>(context, listen: false);
    final categoryController = Provider.of<CategoryController>(context, listen: false);
    final bannerController = Provider.of<BannerController>(context, listen: false);
    final addressController = Provider.of<AddressController>(context, listen: false);
    final productController = Provider.of<ProductController>(context, listen: false);
    final brandController = Provider.of<BrandController>(context, listen: false);
    final featuredDealController = Provider.of<FeaturedDealController>(context, listen: false);
    final notificationController = Provider.of<NotificationController>(context, listen: false);
    final cartController = Provider.of<CartController>(context, listen: false);
    final profileController = Provider.of<ProfileController>(context, listen: false);

    final splashController = Provider.of<SplashController>(context, listen: false);

    final authController = Provider.of<AuthController>(context, listen: false);

    splashController.initConfig(context, null, null);

    // [AI] Batch 1: Critical above-the-fold content (instant render)
    final List<Future> priorityFutures = [
      categoryController.getCategoryList(reload),
      bannerController.getBannerList(),
      productController.getLatestProductList(1, isUpdate: reload),
      productController.getFeaturedProductModel(1, isUpdate: reload),
      productController.getSelectedProductModel(1, isUpdate: reload),
    ];

    // [AI] Batch 2: Secondary sections (staggered to prevent 508 resource limits on shared hosting)
    final List<Future> secondaryFutures = [
      productController.getHomeCategoryProductList(reload),
      shopController.getTopSellerList(offset: 1, isUpdate: reload),
      brandController.getBrandList(offset: 1, isUpdate: reload),
      featuredDealController.getFeaturedDealList(),
      productController.getRecommendedProduct(),
      productController.getClearanceAllProductList(1, isUpdate: reload),
      productController.loadRecentlyViewedProducts(),
      cartController.getCartData(context),
      addressController.getAddressList(),
    ];

    if (notificationController.notificationModel == null || reload) {
      secondaryFutures.add(notificationController.getNotificationList(1));
    }
    if (authController.isLoggedIn() && (profileController.userInfoModel == null || reload)) {
      secondaryFutures.add(profileController.getUserInfo(context));
    }

    if (reload) {
      await Future.wait(priorityFutures.map((f) => f.catchError((e) => debugPrint('Home reload error: $e'))));
      await Future.wait(secondaryFutures.map((f) => f.catchError((e) => debugPrint('Home reload error: $e'))));
    } else {
      Future.wait(priorityFutures.map((f) => f.catchError((e) => debugPrint('Priority load error: $e')))).then((_) {
        Future.wait(secondaryFutures.map((f) => f.catchError((e) => debugPrint('Secondary load error: $e'))));
      });
    }
  }
}

class _HomePageState extends State<HomePage> with AutomaticKeepAliveClientMixin {
  final ScrollController _scrollController = ScrollController();

  @override
  bool get wantKeepAlive => true;

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
    super.build(context);
    final ConfigModel? configModel = Provider.of<SplashController>(context, listen: false).configModel;


    return Scaffold(resizeToAvoidBottomInset: false,
      body: SafeArea(child: RefreshIndicator(
        onRefresh: () async {
          await HomePage.loadData(true);
        },
        child: CustomScrollView(
          controller: _scrollController,
          slivers: [
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
                  // Premium Two-Tone Brand Wordmark — "Victorious" Gold + "MARKET" White
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
                                  fontSize: 23,
                                  letterSpacing: 0.6,
                                  height: 1.1,
                                  color: Colors.white, // ShaderMask overrides to gold gradient
                                  shadows: [
                                    Shadow(
                                      color: Colors.black.withValues(alpha: 0.35),
                                      offset: const Offset(0, 2),
                                      blurRadius: 6,
                                    ),
                                  ],
                                ),
                              ),
                              const TextSpan(text: '\n'),
                              WidgetSpan(
                                child: ShaderMask(
                                  blendMode: BlendMode.srcIn,
                                  shaderCallback: (_) => const LinearGradient(
                                    colors: [Colors.white, Color(0xFFF0F0F0)],
                                  ).createShader(const Rect.fromLTWH(0, 0, 140, 24)),
                                  child: Text(
                                    'MARKET',
                                    style: TextStyle(
                                      fontFamily: 'Ubuntu',
                                      fontWeight: FontWeight.w900,
                                      fontSize: 20,
                                      letterSpacing: 5.0,
                                      height: 1.0,
                                      color: Colors.white,
                                      shadows: [
                                        Shadow(
                                          color: Colors.black.withValues(alpha: 0.3),
                                          offset: const Offset(0, 2),
                                          blurRadius: 5,
                                        ),
                                      ],
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

                  // Actions: CALL TO ORDER (2-line) + Clean Notification Bell
                  Expanded(
                    flex: 6,
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.end,
                      crossAxisAlignment: CrossAxisAlignment.center,
                      children: [
                        // Call to Order 2-line Stack (CALL TO ORDER: \n +PHONE_NUMBER)
                        if (configModel?.companyPhone != null && configModel!.companyPhone!.isNotEmpty)
                          InkWell(
                            onTap: () async {
                              final Uri launchUri = Uri(scheme: 'tel', path: configModel.companyPhone);
                              if (await canLaunchUrl(launchUri)) {
                                await launchUrl(launchUri);
                              }
                            },
                            child: Column(
                              mainAxisSize: MainAxisSize.min,
                              crossAxisAlignment: CrossAxisAlignment.end,
                              children: [
                                Text(
                                  'CALL TO ORDER:',
                                  style: textBold.copyWith(
                                    color: const Color(0xFFFFD700),
                                    fontSize: 9,
                                    letterSpacing: 0.5,
                                  ),
                                ),
                                const SizedBox(height: 1),
                                Text(
                                  configModel.companyPhone!,
                                  style: textBold.copyWith(
                                    color: Colors.white,
                                    fontSize: 11,
                                    letterSpacing: 0.3,
                                  ),
                                ),
                              ],
                            ),
                          ),

                        const SizedBox(width: 10),

                        // Notification Bell with Accurate Unread Count Badge
                        Consumer<NotificationController>(
                          builder: (context, notificationProvider, _) {
                            final int unreadCount = notificationProvider.getUnreadNotificationCount();

                            return InkWell(
                              onTap: () => RouterHelper.getNotificationRoute(action: RouteAction.push),
                              child: Stack(
                                clipBehavior: Clip.none,
                                alignment: Alignment.center,
                                children: [
                                  const Icon(
                                    Icons.notifications_none_rounded,
                                    color: Colors.white,
                                    size: 24,
                                  ),
                                  if (unreadCount > 0)
                                    Positioned(
                                      top: -3,
                                      right: -4,
                                      child: Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
                                        decoration: const BoxDecoration(
                                          color: Color(0xFFFFD700),
                                          shape: BoxShape.circle,
                                        ),
                                        constraints: const BoxConstraints(
                                          minWidth: 14,
                                          minHeight: 14,
                                        ),
                                        child: Center(
                                          child: Text(
                                            unreadCount > 99 ? '99+' : '$unreadCount',
                                            style: textBold.copyWith(
                                              color: const Color(0xFF4A148C),
                                              fontSize: 8,
                                              height: 1.0,
                                            ),
                                          ),
                                        ),
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
              ),
          ),
              
            SliverToBoxAdapter(child: Provider.of<SplashController>(context, listen: false).configModel!.announcement!.status == '1'?
            Consumer<SplashController>(
              builder: (context, announcement, _){
                return (announcement.configModel!.announcement!.announcement != null && announcement.onOff)?
                AnnouncementWidget(announcement: announcement.configModel!.announcement):const SizedBox();
              }): const SizedBox()),

            SliverPersistentHeader(pinned: true, delegate: SliverSearchDelegate(
              child: InkWell(
                onTap: ()=> RouterHelper.getSearchRoute(action: RouteAction.push),
                child: const Hero(tag: 'search', child: Material(child: SearchHomePageWidget())),
              ),
            )),



            SliverToBoxAdapter(child: BannersWidget()),


            SliverToBoxAdapter(
              child: CategoryListWidget(isHomePage: true),
            ),
            SliverToBoxAdapter(child: SizedBox(height: Dimensions.paddingSizeDefault)),

            SliverToBoxAdapter(
              child: Column(
                children: [
                  Consumer<FlashDealController>(builder: (context, megaDeal, child) {
                    return (megaDeal.flashDeal != null && megaDeal.flashDealList.isNotEmpty) ? Column(children: [

                        Padding(
                        padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
                        child: FlashDealBar(
                          title: getTranslated('flash_deal', context)!.toUpperCase(),
                          eventDuration: megaDeal.flashDeal != null ? megaDeal.duration : null,
                          onTap: () {
                            RouterHelper.getFlashDealScreenViewRoute();
                          },
                        ),
                      ),
                      const SizedBox(height: Dimensions.paddingSizeSmall),

                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
                        child: Text(getTranslated('hurry_up_the_offer_is_limited_grab_while_it_lasts', context)??'',
                          style: textRegular.copyWith(color: Provider.of<ThemeController>(context, listen: false).darkTheme?
                          Theme.of(context).hintColor : Theme.of(context).primaryColor, fontSize: Dimensions.fontSizeDefault),
                          textAlign: TextAlign.center,
                        ),
                      ),
                      const SizedBox(height: Dimensions.paddingSizeSmall),

                      const FlashDealsListWidget()

                    ]) : (!megaDeal.hasLoaded ? const FlashDealShimmer() : const SizedBox.shrink());
                  }),
                  const SizedBox(height: Dimensions.paddingSizeExtraSmall),
                ],
              ),
            ),


            SliverToBoxAdapter(
              child: Consumer<FeaturedDealController>(
                  builder: (context, featuredDealProvider, child) {
                    return  featuredDealProvider.featuredDealProductList != null? featuredDealProvider.featuredDealProductList!.isNotEmpty ?
                    Column(
                      children: [
                        Stack(children: [
                          Container(
                            width: MediaQuery.of(context).size.width,
                            height: 150,
                            color: Provider.of<ThemeController>(context, listen: false).darkTheme ?
                            Theme.of(context).highlightColor
                                : Theme.of(context).colorScheme.onTertiary,
                          ),

                          Column(children: [
                            Padding(
                              padding: const EdgeInsets.symmetric(vertical: Dimensions.paddingSizeDefault),
                              child: TitleRowWidget(
                                title: '${getTranslated('featured_deals', context)}',
                                onTap: () {
                                  RouterHelper.getFeaturedDealScreenViewRoute();
                                },
                              ),
                            ),

                            const FeaturedDealsListWidget(),
                          ]),
                        ]),

                        const SizedBox(height: Dimensions.paddingSizeDefault),
                      ],
                    ) : const SizedBox.shrink() : const FindWhatYouNeedShimmer();}
              ),
            ),


            SliverToBoxAdapter(
              child: const ClearanceListWidget(),
            ),
            SliverToBoxAdapter(child: SizedBox(height: Dimensions.paddingSizeDefault)),


            SliverToBoxAdapter(
              child: Consumer<BannerController>(builder: (context, footerBannerProvider, child){
                return footerBannerProvider.footerBannerList != null && footerBannerProvider.footerBannerList!.isNotEmpty?
                Padding(padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
                    child: SingleBannersWidget( bannerModel : footerBannerProvider.footerBannerList?[0])):
                const SizedBox();
              }),
            ),
            SliverToBoxAdapter(child: SizedBox(height: Dimensions.paddingSizeDefault)),


            SliverToBoxAdapter(
              child: const FeaturedProductWidget(),
            ),
            SliverToBoxAdapter(child: SizedBox(height: Dimensions.paddingSizeDefault)),

            if(!singleVendor)
            SliverToBoxAdapter(
              child: Container(
                padding: EdgeInsets.only(top: Dimensions.paddingSizeSmall),
                color: Theme.of(context).cardColor,
                child: Column(
                  children: [
                    Consumer<ShopController>(
                        builder: (context, topSellerProvider, child) {
                          return (topSellerProvider.topSellerModel != null && (topSellerProvider.topSellerModel!.sellers!=null && topSellerProvider.topSellerModel!.sellers!.isNotEmpty))?
                          TitleRowWidget(title: getTranslated('top_seller', context),
                              onTap: ()=> RouterHelper.getAllTopSellerRoute(action: RouteAction.push, title: 'top_seller')) :
                          const SizedBox();
                        }),
                    singleVendor ? const SizedBox(height: 0):const SizedBox(height: Dimensions.paddingSizeSmall),

                    singleVendor ? const SizedBox() :
                    Consumer<ShopController>(
                        builder: (context, topSellerProvider, child) {
                          return (topSellerProvider.topSellerModel != null && (topSellerProvider.topSellerModel!.sellers!=null && topSellerProvider.topSellerModel!.sellers!.isNotEmpty))?
                          Padding(padding: const EdgeInsets.only(bottom: Dimensions.paddingSizeDefault),
                              child: SizedBox(height: ResponsiveHelper.isTab(context)? 170 : 150, child: const TopSellerWidget())):const SizedBox();}

                    )
                  ],
                ),
              ),
            ),

            if(!singleVendor)
            SliverToBoxAdapter(child: SizedBox(height: Dimensions.paddingSizeDefault)),

            SliverToBoxAdapter(
              child: Padding(
                padding: EdgeInsets.only(bottom: Dimensions.paddingSizeDefault),
                child: RecommendedProductWidget()
              ),
            ),


            SliverToBoxAdapter(
              child: Padding(
                padding: EdgeInsets.only(bottom: Dimensions.paddingSizeDefault),
                child: LatestProductListWidget()
              ),
            ),


            if(configModel!.brandSetting == "1")
            SliverToBoxAdapter(
              child: Column(
                children: [
                  const BrandListWidget(isHomePage: true),
                  const SizedBox(height: Dimensions.paddingSizeDefault),
                ],
              )
            ),

            const HomeCategoryProductWidget(isHomePage: true),

            SliverToBoxAdapter(
              child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                if((Provider.of<BannerController>(context, listen: false).footerBannerList?.length ?? 0) > 1)
                const SizedBox(height: Dimensions.paddingSizeDefault),

                const FooterBannerSliderWidget(),
              ]),
            ),



            SliverPersistentHeader(pinned: true, delegate: SliverDelegate(
              height: 50,
              child: Align(
                alignment: Alignment.topLeft,
                child: Container(color: Theme.of(context).scaffoldBackgroundColor, child: const ProductPopupFilterWidget()),
              ),
            )),

            HomeProductListWidget(scrollController: _scrollController),


          ],
        ),
        ),
      ),
      // ),
    );
  }
}

class SliverDelegate extends SliverPersistentHeaderDelegate {
  Widget child;
  double height;
  SliverDelegate({required this.child, this.height = 50});

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


class SliverSearchDelegate extends SliverPersistentHeaderDelegate {
  Widget child;
  double height;
  SliverSearchDelegate({required this.child, this.height = 70});

  @override
  Widget build(BuildContext context, double shrinkOffset, bool overlapsContent) {
    return child;
  }

  @override
  double get maxExtent => height;

  @override
  double get minExtent => height;

  @override
  bool shouldRebuild(covariant SliverSearchDelegate oldDelegate) {
    return oldDelegate.height != height || oldDelegate.child != child;
  }
}