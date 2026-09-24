import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/features/profile/controllers/profile_contrroller.dart';
import 'package:flutter_sixvalley_ecommerce/features/splash/domain/models/business_pages_model.dart';
import 'package:flutter_sixvalley_ecommerce/helper/route_healper.dart';
import 'package:flutter_sixvalley_ecommerce/utill/app_constants.dart';
import 'package:flutter_sixvalley_ecommerce/features/more/widgets/logout_confirm_bottom_sheet_widget.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/features/auth/controllers/auth_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/splash/controllers/splash_controller.dart';
import 'package:flutter_sixvalley_ecommerce/theme/controllers/theme_controller.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';
import 'package:flutter_sixvalley_ecommerce/utill/images.dart';
import 'package:flutter_sixvalley_ecommerce/features/more/widgets/profile_info_section_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/more/widgets/more_horizontal_section_widget.dart';
import 'package:provider/provider.dart';
import 'package:flutter_sixvalley_ecommerce/features/more/widgets/title_button_widget.dart';


class MoreScreen extends StatefulWidget {
  const MoreScreen({super.key});
  @override
  State<MoreScreen> createState() => _MoreScreenState();
}

class _MoreScreenState extends State<MoreScreen> with AutomaticKeepAliveClientMixin {
  @override
  bool get wantKeepAlive => true;

  String? version;
  bool singleVendor = false;


  @override
  void initState() {

    if(Provider.of<AuthController>(context, listen: false).isLoggedIn()) {
      version = Provider.of<SplashController>(context,listen: false).configModel!.softwareVersion ?? 'version';
      Provider.of<ProfileController>(context, listen: false).getUserInfo(context);

    }
    singleVendor = Provider.of<SplashController>(context, listen: false).configModel?.businessMode == "single";

    super.initState();
  }


  @override
  Widget build(BuildContext context) {
    super.build(context);
    // var authController = Provider.of<AuthController>(context, listen: false);

    return Scaffold(
      body: CustomScrollView(slivers: [
        SliverAppBar(
          floating: true,
          elevation: 0,
          expandedHeight: 160,
          pinned: true,
          centerTitle: false,
          automaticallyImplyLeading: false,
          backgroundColor: Theme.of(context).highlightColor,
          collapsedHeight: 160,
          flexibleSpace: const ProfileInfoSectionWidget()
        ),

        SliverToBoxAdapter(child: Container(decoration: BoxDecoration(color: Theme.of(context).scaffoldBackgroundColor),
          child: Consumer<AuthController>(
            builder: (ctx, authController, _) {
              return Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
                  const Padding(padding: EdgeInsets.symmetric(vertical: Dimensions.paddingSizeSmall),
                    child: Center(child: MoreHorizontalSection())),

                  // [AI] 5% Victorious Cashback Gold & Purple Card
                  _buildCashbackRewardCard(context),

                  Padding(padding: const EdgeInsets.fromLTRB( Dimensions.paddingSizeDefault,
                      Dimensions.paddingSizeDefault,  Dimensions.paddingSizeDefault,0),
                    child: Text(getTranslated('general', context)??'',
                      style: textRegular.copyWith(fontSize: Dimensions.fontSizeExtraLarge,
                          color: Theme.of(context).colorScheme.onPrimary), ),),

                  Consumer<SplashController>(
                      builder: (context, splashController, _) {
                        return Padding(padding:  const EdgeInsets.all(Dimensions.paddingSizeDefault),
                          child: Container(padding:  const EdgeInsets.all(Dimensions.paddingSizeSmall),
                            decoration: BoxDecoration(
                              borderRadius: BorderRadius.circular(16),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black.withValues(alpha: 0.04),
                                  blurRadius: 10,
                                  offset: const Offset(0, 4),
                                ),
                              ],
                              border: Border.all(
                                color: Theme.of(context).primaryColor.withValues(alpha: 0.08),
                              ),
                              color: Provider.of<ThemeController>(context).darkTheme ?
                              Colors.white.withValues(alpha:.05) : Theme.of(context).cardColor,
                            ),
                            child: Column(children: [


                              MenuButtonWidget(image: Images.trackOrderIcon, title: getTranslated('TRACK_ORDER', context),
                                onTap: () {
                                  RouterHelper.getGuestTrackOrderRoute(action: RouteAction.push);
                                },
                              ),

                              if(authController.isLoggedIn())
                                MenuButtonWidget(image: Images.user, title: getTranslated('profile', context),
                                  onTap: () {
                                    RouterHelper.getProfileScreen1Route(action: RouteAction.push);
                                  },
                                ),

                              if(authController.isLoggedIn())
                                MenuButtonWidget(image: Images.loyaltyPoint, title: getTranslated('cashback', context) ?? 'Cashback',
                                  onTap: () {
                                    RouterHelper.getCashbackRoute(action: RouteAction.push);
                                  },
                                ),

                              if(authController.isLoggedIn())
                                MenuButtonWidget(image: Images.shoppingImage, title: getTranslated('my_pickup_reservations', context) ?? 'My Pickup Reservations',
                                  onTap: () {
                                    RouterHelper.getMyReservationsRoute(action: RouteAction.push);
                                  },
                                ),

                              MenuButtonWidget(image: Images.address, title: getTranslated('addresses', context),
                                onTap: () {
                                  RouterHelper.getAddressListScreen(action: RouteAction.push);
                                },

                              ),


                              MenuButtonWidget(image: Images.category, title: getTranslated('CATEGORY', context),
                                onTap: () {
                                  RouterHelper.getCategoryScreenRoute(action: RouteAction.push);
                                },
                              ),

                              MenuButtonWidget(image: Images.notification, title: getTranslated('notification', context,),
                                isNotification: true,
                                onTap: () {
                                  RouterHelper.getNotificationRoute(action: RouteAction.push);
                                },
                              ),

                              MenuButtonWidget(image: Images.settings, title: getTranslated('settings', context),
                                onTap: () {
                                  RouterHelper.getSettingsRoute(action: RouteAction.push);
                                },
                              ),
                            ]),
                          ),
                        );
                      }
                  ),


                  Padding(padding: const EdgeInsets.fromLTRB( Dimensions.paddingSizeDefault,
                    Dimensions.paddingSizeDefault,  Dimensions.paddingSizeDefault,0),
                    child: Text(getTranslated('help_and_support', context)??'',
                      style: textRegular.copyWith(fontSize: Dimensions.fontSizeExtraLarge,
                        color: Theme.of(context).colorScheme.onPrimary))
                  ),


                  Padding(padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                    child: Container(
                      padding: const EdgeInsets.all(Dimensions.paddingSizeSmall),
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(16),
                        boxShadow: [
                          BoxShadow(
                            color: Colors.black.withValues(alpha: 0.04),
                            blurRadius: 10,
                            offset: const Offset(0, 4),
                          ),
                        ],
                        border: Border.all(
                          color: Theme.of(context).primaryColor.withValues(alpha: 0.08),
                        ),
                        color: Provider.of<ThemeController>(context).darkTheme ?
                        Colors.white.withValues(alpha:.05) : Theme.of(context).cardColor,
                      ),
                      child: Consumer<SplashController>(
                        builder: (context, splashController, _){
                          return Column(children: [
                            MenuButtonWidget(image: Images.callIcon, title: getTranslated('contact_us', context),
                              onTap: () {
                                RouterHelper.getContactUsScreenRoute();
                              },
                            ),

                            MenuButtonWidget(image: Images.preference, title: getTranslated('support_ticket', context),
                              onTap: () {
                                RouterHelper.getSupportTicketRoute(action: RouteAction.push);
                              },
                            ),


                            if(splashController.defaultBusinessPages != null && splashController.defaultBusinessPages!.isNotEmpty)...[
                              if(getPageBySlug('terms-and-conditions', splashController.defaultBusinessPages) != null)
                                MenuButtonWidget(image: Images.termCondition, title: getTranslated('terms_condition', context),
                                    onTap: () => RouterHelper.getHtmlViewRoute(
                                        page: getPageBySlug('terms-and-conditions', splashController.defaultBusinessPages)!)),


                              if(getPageBySlug('privacy-policy', splashController.defaultBusinessPages) != null)
                                MenuButtonWidget(image: Images.privacyPolicy, title: getTranslated('privacy_policy', context),
                                    onTap: () => RouterHelper.getHtmlViewRoute(page: getPageBySlug('privacy-policy', splashController.defaultBusinessPages)!)),


                              if(getPageBySlug('refund-policy', splashController.defaultBusinessPages) != null)
                                MenuButtonWidget(image: Images.termCondition, title: getTranslated('refund_policy', context),
                                    onTap: () => RouterHelper.getHtmlViewRoute(page: getPageBySlug('refund-policy', splashController.defaultBusinessPages)!)),

                              if(getPageBySlug('return-policy', splashController.defaultBusinessPages) != null)
                                MenuButtonWidget(image: Images.termCondition, title: getTranslated('return_policy', context),
                                    onTap: () => RouterHelper.getHtmlViewRoute(page: getPageBySlug('return-policy', splashController.defaultBusinessPages)!)),

                              if(getPageBySlug('cancellation-policy', splashController.defaultBusinessPages) != null)
                                MenuButtonWidget(image: Images.termCondition, title: getTranslated('cancellation_policy', context),
                                    onTap: () => RouterHelper.getHtmlViewRoute(page: getPageBySlug('cancellation-policy', splashController.defaultBusinessPages)!)),

                              if(getPageBySlug('shipping-policy', splashController.defaultBusinessPages) != null)
                                MenuButtonWidget(image: Images.termCondition, title: getTranslated('shipping_policy', context),
                                    onTap: () => RouterHelper.getHtmlViewRoute(page: getPageBySlug('shipping-policy', splashController.defaultBusinessPages)!)),
                            ],


                            MenuButtonWidget(image: Images.faq, title: getTranslated('faq', context),
                              onTap: () {
                                RouterHelper.getFaqRoute(action: RouteAction.push);
                              },
                            ),

                            if(getPageBySlug('about-us', splashController.defaultBusinessPages) != null)
                              MenuButtonWidget(image: Images.user, title: getTranslated('about_us', context),
                                onTap: () => RouterHelper.getHtmlViewRoute(
                                  page: getPageBySlug('about-us', splashController.defaultBusinessPages)!)),


                            if(splashController.businessPages != null && splashController.businessPages!.isNotEmpty)
                              ListView.builder(
                                  itemCount: splashController.businessPages?.length,
                                  shrinkWrap: true,
                                  padding: EdgeInsets.zero,
                                  physics: const NeverScrollableScrollPhysics(),
                                  itemBuilder: (context, index) {
                                    return MenuButtonWidget(image: Images.termCondition, title: splashController.businessPages?[index].title,
                                      onTap: () {
                                        RouterHelper.getHtmlViewRoute(page: splashController.businessPages![index]);
                                      },
                                    );
                                  }
                              )


                          ]);
                        }
                    ))
                  ),


                  ListTile(
                    leading: SizedBox(width: 30, child: Image.asset(Images.logOut, color: Theme.of(context).primaryColor,)),
                    title: Text(!authController.isLoggedIn() ? getTranslated('sign_in', context)! : getTranslated('sign_out', context)!,
                      style: titilliumRegular.copyWith(fontSize: Dimensions.fontSizeLarge)
                    ),
                    onTap: (){
                      if(!authController.isLoggedIn()){
                        RouterHelper.getLoginRoute(action: RouteAction.push, fromPage: '${RouterHelper.dashboardScreen}?page=more');
                      } else {
                        showModalBottomSheet(backgroundColor: Colors.transparent,
                          context: context, builder: (_)=>  const LogoutCustomBottomSheetWidget()
                        );
                      }
                    },
                  ),

                  Padding(
                    padding: const EdgeInsets.only(bottom: Dimensions.paddingSizeDefault),
                    child: Row(mainAxisAlignment: MainAxisAlignment.center, children: [
                      Text(
                        '${getTranslated('version', context)} ${AppConstants.appVersion}',
                        style: textRegular.copyWith(
                            fontSize: Dimensions.fontSizeLarge,
                            color: Theme.of(context).hintColor),
                      ),
                    ]),
                  ),
                ]);
            }
          ),
        )),
      ]),
    );
  }


  BusinessPageModel? getPageBySlug(String slug, List<BusinessPageModel>? pagesList) {
    BusinessPageModel? pageModel;
    if(pagesList != null && pagesList.isNotEmpty){
      for (var page in pagesList) {
        if(page.slug == slug) {
          pageModel = page;
        }
      }
    }
    return pageModel;
  }

  Widget _buildCashbackRewardCard(BuildContext context) {
    return Consumer<ProfileController>(
      builder: (context, profile, child) {
        final double walletBalance = profile.userInfoModel?.walletBalance ?? 0.0;
        final bool isLoggedIn = Provider.of<AuthController>(context, listen: false).isLoggedIn();

        return Container(
          margin: const EdgeInsets.symmetric(
            horizontal: Dimensions.paddingSizeDefault,
            vertical: Dimensions.paddingSizeSmall,
          ),
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [Color(0xFF3E0075), Color(0xFF1F003B)],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            borderRadius: BorderRadius.circular(16),
            boxShadow: [
              BoxShadow(
                color: const Color(0xFF3E0075).withValues(alpha: 0.35),
                blurRadius: 12,
                offset: const Offset(0, 4),
              ),
            ],
            border: Border.all(
              color: const Color(0xFFFFD700).withValues(alpha: 0.35),
              width: 1.2,
            ),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.all(6),
                        decoration: BoxDecoration(
                          color: const Color(0xFFFFD700).withValues(alpha: 0.15),
                          shape: BoxShape.circle,
                        ),
                        child: const Icon(
                          Icons.stars_rounded,
                          color: Color(0xFFFFD700),
                          size: 20,
                        ),
                      ),
                      const SizedBox(width: 8),
                      Text(
                        'VICTORIOUS REWARDS',
                        style: textBold.copyWith(
                          fontSize: Dimensions.fontSizeExtraSmall,
                          color: const Color(0xFFFFD700),
                          letterSpacing: 1.2,
                        ),
                      ),
                    ],
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: const Color(0xFFFFD700),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      '5% CASHBACK',
                      style: textBold.copyWith(
                        fontSize: 10,
                        color: const Color(0xFF3E0075),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Text(
                '5% Guaranteed Cashback on All Orders',
                style: textBold.copyWith(
                  fontSize: Dimensions.fontSizeLarge,
                  color: Colors.white,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                'Earn 5% automatic cashback credited directly to your wallet upon every completed pickup or delivery.',
                style: textRegular.copyWith(
                  fontSize: Dimensions.fontSizeSmall,
                  color: Colors.white.withValues(alpha: 0.8),
                ),
              ),
              if (isLoggedIn && walletBalance > 0) ...[
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(
                      color: Colors.white.withValues(alpha: 0.15),
                    ),
                  ),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'Wallet Balance Available',
                        style: textRegular.copyWith(
                          fontSize: Dimensions.fontSizeSmall,
                          color: Colors.white70,
                        ),
                      ),
                      Text(
                        '₦${walletBalance.toStringAsFixed(2)}',
                        style: textBold.copyWith(
                          fontSize: Dimensions.fontSizeDefault,
                          color: const Color(0xFFFFD700),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ],
          ),
        );
      },
    );
  }

}
