
import 'package:custom_map_markers/custom_map_markers.dart';
import 'package:expandable_bottom_sheet/expandable_bottom_sheet.dart';
import 'package:flutter/material.dart';
import 'package:get/get.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:sixvalley_delivery_boy/features/live_tracking/controllers/rider_controller.dart';
import 'package:sixvalley_delivery_boy/features/live_tracking/widgets/expendale_bottom_sheet_widget.dart';
import 'package:sixvalley_delivery_boy/features/order/controllers/order_controller.dart';
import 'package:sixvalley_delivery_boy/features/order/domain/models/order_model.dart';
import 'package:sixvalley_delivery_boy/features/splash/controllers/splash_controller.dart';
import 'package:sixvalley_delivery_boy/features/splash/domain/models/config_model.dart';
import 'package:sixvalley_delivery_boy/utill/dimensions.dart';
import 'package:sixvalley_delivery_boy/utill/styles.dart';

class OrderLiveTrackingScreen extends StatefulWidget {
  final OrderModel? orderModel;
  const OrderLiveTrackingScreen({Key? key, this.orderModel}) : super(key: key);

  @override
  State<OrderLiveTrackingScreen> createState() => _OrderLiveTrackingScreenState();
}

class _OrderLiveTrackingScreenState extends State<OrderLiveTrackingScreen> {

  @override
  void initState() {
    super.initState();
    Get.find<RiderController>().initialPosition;

    Future.delayed(const Duration(seconds: 10)).then((value){
      Get.find<RiderController>().getCurrentLocation();
    });
  }

  @override
  Widget build(BuildContext context) {

    DefaultLocation? dLocation = Get.find<SplashController>().configModel?.defaultLocation;
    LatLng _defaut = LatLng(double.parse(dLocation?.lat ?? '0'), double.parse(dLocation?.lng ?? '0'));

    if(widget.orderModel!.shippingAddress != null){
      Get.find<OrderController>().setSelectedOrderLatLng(
        LatLng(
          (widget.orderModel!.shippingAddress!.latitude != null && widget.orderModel!.shippingAddress!.latitude != '0' && widget.orderModel!.shippingAddress!.latitude != '') ?
          double.parse(widget.orderModel!.shippingAddress!.latitude!) : _defaut.latitude,

          (widget.orderModel!.shippingAddress!.longitude != null && widget.orderModel!.shippingAddress!.longitude != '0' && widget.orderModel!.shippingAddress!.longitude != '') ?
          double.parse(widget.orderModel!.shippingAddress!.longitude!) :
          _defaut.longitude
        ));
    }

    return Scaffold(
      body: GetBuilder<RiderController>(
          builder: (riderController) {
            return ExpandableBottomSheet(

              background: Stack(clipBehavior: Clip.none, children: [

                  CustomGoogleMapMarkerBuilder(
                      customMarkers: riderController.customMarkers,
                      builder: (context, markers) {
                        return GoogleMap(
                          mapType: MapType.terrain,
                          initialCameraPosition:  CameraPosition(
                            target:  riderController.initialPosition, zoom: 16),
                          onMapCreated: (GoogleMapController controller){
                            riderController.mapController = controller;
                            LatLng destination = widget.orderModel!.shippingAddress != null?
                            LatLng(double.parse(widget.orderModel!.shippingAddress!.latitude!),
                                double.parse(widget.orderModel!.shippingAddress!.longitude!))  : riderController.initialPosition;
                           riderController.getPolyline(from: riderController.initialPosition, to: destination);
                           riderController.setFromToMarker(from: riderController.initialPosition, to: destination) ;
                          },
                          minMaxZoomPreference: const MinMaxZoomPreference(0, 15),
                          markers: Set<Marker>.of(markers ?? []),
                          polylines: Set<Polyline>.of(riderController.polylines.values),
                          zoomControlsEnabled: false,
                          compassEnabled: false,
                          indoorViewEnabled: true,
                          mapToolbarEnabled: true,
                          onCameraIdle: () {
                          },
                          onCameraMove: ((_position) =>  _position));}),

                  GestureDetector(onTap: ()=> Get.back(),
                    child: Padding(padding: const EdgeInsets.fromLTRB(20,50,0,0),
                      child: Container(width: 40, height: 40,
                      decoration: BoxDecoration(
                        borderRadius: BorderRadius.circular(50),
                        color: Theme.of(context).cardColor,
                        boxShadow: [BoxShadow(color: Theme.of(context).primaryColor.withValues(alpha:.125),
                            blurRadius: 5, spreadRadius: 1, offset: const Offset(0,2))]),
                        child:  Icon(Icons.arrow_back_ios_new, color: Theme.of(context).hintColor))))]),


              persistentHeader:  Container(padding:  EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault,
                    vertical: Dimensions.paddingSizeExtraSmall),
                margin:  EdgeInsets.only(bottom: Dimensions.paddingSizeExtraSmall),
                decoration: BoxDecoration(borderRadius: BorderRadius.circular(Dimensions.paddingSizeDefault),
                    color: Theme.of(context).primaryColor),
                child: Text('${riderController.distance != null? riderController.distance!.toStringAsFixed(2) : 0} km',
                    style: rubikRegular.copyWith(color: Get.isDarkMode ? Theme.of(context).highlightColor : Theme.of(context).cardColor)),),
              persistentContentHeight: riderController.persistentContentHeight,
              expandableContent: RiderBottomSheetWidget(orderModel: widget.orderModel)
            );
          }
      ),
    );
  }
}
