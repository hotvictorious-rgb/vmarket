import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/common/basewidget/show_custom_snakbar_widget.dart';
import 'package:flutter_sixvalley_ecommerce/features/address/domain/models/address_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/address/domain/models/geography_models.dart';
import 'package:flutter_sixvalley_ecommerce/data/model/api_response.dart';
import 'package:flutter_sixvalley_ecommerce/features/address/domain/models/label_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/address/domain/models/restricted_zip_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/address/domain/services/address_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/helper/api_checker.dart';
import 'package:flutter_sixvalley_ecommerce/main.dart';

class AddressController with ChangeNotifier {
  final AddressServiceInterface addressServiceInterface;
  AddressController({required this.addressServiceInterface});

  List<String> _restrictedCountryList = [];
  List<String> get restrictedCountryList =>_restrictedCountryList;
  List<RestrictedZipModel> _restrictedZipList =[];
  List<RestrictedZipModel> get restrictedZipList => _restrictedZipList;
  final List<String> _zipNameList = [];
  List<String> get zipNameList => _zipNameList;
  final TextEditingController _searchZipController = TextEditingController();
  TextEditingController get searchZipController => _searchZipController;
  final TextEditingController _searchCountryController = TextEditingController();
  TextEditingController get searchCountryController => _searchCountryController;
  List<AddressModel>? _addressList;
  List<AddressModel>? get addressList => _addressList;
  

  Future<void> getRestrictedDeliveryCountryList() async {
    ApiResponseModel apiResponse = await addressServiceInterface.getDeliveryRestrictedCountryList();
    if (apiResponse.response != null && apiResponse.response!.statusCode == 200) {
      _restrictedCountryList = [];
      apiResponse.response!.data.forEach((address) => _restrictedCountryList.add(address));
    } else {
      ApiChecker.checkApi( apiResponse);
    }
    notifyListeners();
  }


  Future<void> getRestrictedDeliveryZipList() async {
    ApiResponseModel apiResponse = await addressServiceInterface.getDeliveryRestrictedZipList();
    if (apiResponse.response != null && apiResponse.response?.statusCode == 200) {
      _restrictedZipList = [];
      apiResponse.response!.data.forEach((address) => _restrictedZipList.add(RestrictedZipModel.fromJson(address)));
    } else {
      ApiChecker.checkApi( apiResponse);
    }
    notifyListeners();
  }

  
  Future<void> getDeliveryRestrictedZipBySearch(String searchName) async {
    _restrictedZipList = [];
    ApiResponseModel response = await addressServiceInterface.getDeliveryRestrictedZipBySearch(searchName);
    if(response.response!.statusCode == 200) {
      _restrictedZipList = [];
      response.response!.data.forEach((address) {
        _restrictedZipList.add(RestrictedZipModel.fromJson(address));
      });
    }else {
      ApiChecker.checkApi(response);
    }
   notifyListeners();
  }


  Future<void> getDeliveryRestrictedCountryBySearch( String searchName) async {
    _restrictedCountryList = [];
    ApiResponseModel response = await addressServiceInterface.getDeliveryRestrictedCountryBySearch(searchName);
    if(response.response!.statusCode == 200) {
      _restrictedCountryList = [];
      response.response!.data.forEach((address) => _restrictedCountryList.add(address));
    }else {
      ApiChecker.checkApi(response);
    }
    notifyListeners();
  }


  bool _isLoading = false;
  bool get isLoading => _isLoading;



  Future<List<AddressModel>?> getAddressList({bool fromRemove = false, bool isShipping = false, bool isBilling = false, bool all = false, bool reload = false }) async {
    if (!reload && !fromRemove && _addressList != null && _addressList!.isNotEmpty) {
      return _addressList;
    }
    if (fromRemove || reload) {
      _addressList = null;
    }
    _addressList = await addressServiceInterface.getList(isShipping: isShipping, isBilling: isBilling, fromRemove: fromRemove, all: all);
    notifyListeners();
    return _addressList;
  }




  Future<void> deleteAddress(int id) async {
    ApiResponseModel apiResponse = await addressServiceInterface.delete(id);
    if (apiResponse.response != null && apiResponse.response!.statusCode == 200) {
      showCustomSnackBarWidget(apiResponse.response!.data['message'], Get.context!, snackBarType: SnackBarType.success);
      getAddressList(fromRemove: true);
    } else {
      ApiChecker.checkApi( apiResponse);
    }
    notifyListeners();
  }

  Future<ApiResponseModel> addAddress(AddressModel addressModel) async {
    _isLoading = true;
    notifyListeners();
    ApiResponseModel apiResponse = await addressServiceInterface.add(addressModel);
    _isLoading = false;
    if (apiResponse.response != null && apiResponse.response!.statusCode == 200) {
      showCustomSnackBarWidget(apiResponse.response!.data["message"], Get.context!, snackBarType: SnackBarType.success);
      getAddressList();
    } else {
      ApiChecker.checkApi(apiResponse);
    }
    notifyListeners();
    return apiResponse;
  }


  Future<void> updateAddress(BuildContext context, {required AddressModel addressModel, int? addressId}) async {
    _isLoading = true;
    notifyListeners();
    ApiResponseModel apiResponse = await addressServiceInterface.update(addressModel.toJson(), addressId!);
    _isLoading = false;
    if (apiResponse.response != null && apiResponse.response!.statusCode == 200) {
      Navigator.pop(Get.context!);
      getAddressList();
      showCustomSnackBarWidget(apiResponse.response!.data["message"], Get.context!, snackBarType: SnackBarType.success);
    }else {
      ApiChecker.checkApi(apiResponse);
    }

    notifyListeners();
  }

  void setZip(String zip){
    _searchZipController.text = zip;
    notifyListeners();
  }
  
  void setCountry(String country){
    _searchCountryController.text = country;
    notifyListeners();
  }

  


  List<LabelAsModel> addressTypeList = [];
  int _selectAddressIndex = 0;

  int get selectAddressIndex => _selectAddressIndex;

  void updateAddressIndex(int index, bool notify) {
    _selectAddressIndex = index;
    if(notify) {
      notifyListeners();
    }
  }

  Future<List<LabelAsModel>> getAddressType() async {
    if (addressTypeList.isEmpty) {
      addressTypeList = [];
      addressTypeList = addressServiceInterface.getAddressType();
    }
    return addressTypeList;
  }

  void resetAddressList({bool isUpdate = true}) {
    _addressList = [];
    if(isUpdate){
      notifyListeners();
    }
  }

  // [AI] Canonical Geography Management (Country -> State -> LGA)
  List<CountryModel> _countryList = [];
  List<CountryModel> get countryList => _countryList;
  List<StateModel> _stateList = [];
  List<StateModel> get stateList => _stateList;
  List<LgaModel> _lgaList = [];
  List<LgaModel> get lgaList => _lgaList;

  CountryModel? _selectedCountry;
  CountryModel? get selectedCountry => _selectedCountry;
  StateModel? _selectedState;
  StateModel? get selectedState => _selectedState;
  LgaModel? _selectedLga;
  LgaModel? get selectedLga => _selectedLga;

  bool _isGeographyLoading = false;
  bool get isGeographyLoading => _isGeographyLoading;

  Future<void> getCountries({bool reload = false}) async {
    if (_countryList.isNotEmpty && !reload) return;
    _isGeographyLoading = true;
    notifyListeners();
    _countryList = await addressServiceInterface.getCountries();
    _isGeographyLoading = false;
    if (_countryList.isNotEmpty && _selectedCountry == null) {
      int ngIndex = _countryList.indexWhere((c) => (c.name ?? '').toLowerCase() == 'nigeria');
      _selectedCountry = ngIndex != -1 ? _countryList[ngIndex] : _countryList.first;
      if (_selectedCountry?.id != null) {
        getStates(_selectedCountry!.id!);
      }
    }
    notifyListeners();
  }

  Future<void> getStates(int countryId, {int? preSelectStateId}) async {
    _isGeographyLoading = true;
    notifyListeners();
    _stateList = await addressServiceInterface.getStates(countryId);
    _lgaList = [];
    _selectedLga = null;
    if (preSelectStateId != null) {
      int idx = _stateList.indexWhere((s) => s.id == preSelectStateId);
      _selectedState = idx != -1 ? _stateList[idx] : null;
    } else {
      _selectedState = null;
    }
    _isGeographyLoading = false;
    notifyListeners();
  }

  Future<void> getLgas(int stateId, {int? preSelectLgaId}) async {
    _isGeographyLoading = true;
    notifyListeners();
    _lgaList = await addressServiceInterface.getLgas(stateId);
    if (preSelectLgaId != null) {
      int idx = _lgaList.indexWhere((l) => l.id == preSelectLgaId);
      _selectedLga = idx != -1 ? _lgaList[idx] : null;
    } else {
      _selectedLga = null;
    }
    _isGeographyLoading = false;
    notifyListeners();
  }

  void setSelectedCountry(CountryModel? country, {bool loadStates = true}) {
    _selectedCountry = country;
    _selectedState = null;
    _selectedLga = null;
    _stateList = [];
    _lgaList = [];
    if (loadStates && country?.id != null) {
      getStates(country!.id!);
    }
    notifyListeners();
  }

  String? _geographyErrorMessage;
  String? get geographyErrorMessage => _geographyErrorMessage;

  void clearGeographyError() {
    _geographyErrorMessage = null;
    notifyListeners();
  }

  bool isStateLgaMatched({StateModel? state, LgaModel? lga}) {
    final s = state ?? _selectedState;
    final l = lga ?? _selectedLga;
    if (s == null || l == null) return true;
    if (s.id != null && l.stateId != null) {
      return s.id == l.stateId;
    }
    return true;
  }

  void setSelectedState(StateModel? state, {bool loadLgas = true}) {
    _selectedState = state;
    _selectedLga = null;
    _geographyErrorMessage = null;
    _lgaList = [];
    if (loadLgas && state?.id != null) {
      getLgas(state!.id!);
    }
    notifyListeners();
  }

  bool setSelectedLga(LgaModel? lga) {
    if (lga != null && _selectedState != null && lga.stateId != null && _selectedState!.id != null) {
      if (lga.stateId != _selectedState!.id) {
        _geographyErrorMessage = 'Selected LGA does not belong to the chosen State (${_selectedState!.name ?? ''})';
        _selectedLga = null;
        notifyListeners();
        return false;
      }
    }
    _geographyErrorMessage = null;
    _selectedLga = lga;
    notifyListeners();
    return true;
  }

  Future<void> initEditAddress(AddressModel address) async {
    await getCountries();
    if (address.countryId != null) {
      int cIdx = _countryList.indexWhere((c) => c.id == address.countryId);
      if (cIdx != -1) {
        _selectedCountry = _countryList[cIdx];
      }
    }
    if (_selectedCountry?.id != null) {
      await getStates(_selectedCountry!.id!, preSelectStateId: address.stateId);
      if (_selectedState?.id != null) {
        await getLgas(_selectedState!.id!, preSelectLgaId: address.lgaId);
      }
    }
    notifyListeners();
  }

  // [AI] Zero-Key Hierarchical LGA-Scoped Street Suggestions
  List<AddressSuggestionModel> _addressSuggestions = [];
  List<AddressSuggestionModel> get addressSuggestions => _addressSuggestions;

  bool _isSearchingAddress = false;
  bool get isSearchingAddress => _isSearchingAddress;

  void clearAddressSuggestions() {
    _addressSuggestions = [];
    _isSearchingAddress = false;
    notifyListeners();
  }

  Future<void> searchAddressSuggestions(String query) async {
    final clean = query.trim();
    if (_selectedLga?.id == null || clean.length < 2) {
      _addressSuggestions = [];
      _isSearchingAddress = false;
      notifyListeners();
      return;
    }

    _isSearchingAddress = true;
    notifyListeners();

    _addressSuggestions = await addressServiceInterface.autocompleteAddress(_selectedLga!.id!, clean);
    _isSearchingAddress = false;
    notifyListeners();
  }
}

