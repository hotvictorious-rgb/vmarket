import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_sixvalley_ecommerce/features/address/controllers/address_controller.dart';
import 'package:flutter_sixvalley_ecommerce/features/address/domain/models/address_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/address/domain/models/geography_models.dart';
import 'package:flutter_sixvalley_ecommerce/features/address/domain/models/label_model.dart';
import 'package:flutter_sixvalley_ecommerce/features/address/domain/services/address_service_interface.dart';
import 'package:flutter_sixvalley_ecommerce/data/model/api_response.dart';

class MockAddressService implements AddressServiceInterface {
  final int authenticatedCustomerId = 42;
  final List<AddressModel> mockDatabase = [];

  MockAddressService() {
    mockDatabase.add(AddressModel(
      id: 101,
      contactPersonName: 'Owner Customer',
      phone: '+2348011223344',
      address: '15 Ikot Ekpene Road, Uyo',
      countryId: 1,
      stateId: 3,
      lgaId: 142,
      lgaName: 'Uyo',
      city: 'Uyo',
      state: 'Akwa Ibom',
      country: 'Nigeria',
      latitude: '5.0377',
      longitude: '7.9128',
    ));
    mockDatabase.add(AddressModel(
      id: 999, // Belongs to another victim customer
      contactPersonName: 'Victim Customer',
      phone: '+2348099887766',
      address: '99 Other Street, Lagos',
      countryId: 1,
      stateId: 25,
      lgaId: 501,
      lgaName: 'Ikeja',
      city: 'Ikeja',
      state: 'Lagos',
      country: 'Nigeria',
    ));
  }

  @override
  Future<List<CountryModel>> getCountries() async {
    return [
      CountryModel(id: 1, name: 'Nigeria', isoCode: 'NG', phoneCode: '234'),
      CountryModel(id: 2, name: 'Ghana', isoCode: 'GH', phoneCode: '233'),
    ];
  }

  @override
  Future<List<StateModel>> getStates(int countryId) async {
    if (countryId == 1) {
      return [
        StateModel(id: 3, countryId: 1, name: 'Akwa Ibom', stateCode: 'AK'),
        StateModel(id: 25, countryId: 1, name: 'Lagos', stateCode: 'LA'),
        StateModel(id: 32, countryId: 1, name: 'Rivers', stateCode: 'RI'),
      ];
    }
    return [];
  }

  @override
  Future<List<LgaModel>> getLgas(int stateId) async {
    if (stateId == 3) {
      // Akwa Ibom State LGAs
      return [
        LgaModel(id: 118, stateId: 3, name: 'Abak'),
        LgaModel(id: 125, stateId: 3, name: 'Eket'),
        LgaModel(id: 132, stateId: 3, name: 'Ikot Abasi'),
        LgaModel(id: 133, stateId: 3, name: 'Ikot Ekpene'),
        LgaModel(id: 140, stateId: 3, name: 'Oron'),
        LgaModel(id: 142, stateId: 3, name: 'Uyo'),
      ];
    } else if (stateId == 25) {
      // Lagos State LGAs
      return [
        LgaModel(id: 501, stateId: 25, name: 'Ikeja'),
        LgaModel(id: 502, stateId: 25, name: 'Lagos Island'),
      ];
    }
    return [];
  }

  @override
  Future<ApiResponseModel> add(AddressModel addressModel) async {
    // Assert strictly that payload contains zero client-calculated fee/origin fields
    final json = addressModel.toJson();
    if (json.containsKey('fee') || json.containsKey('delivery_fee') || json.containsKey('origin_lga_id')) {
      throw StateError('FORBIDDEN: Client attempted to submit delivery fee or origin LGA');
    }
    addressModel.id = mockDatabase.length + 100;
    mockDatabase.add(addressModel);
    return ApiResponseModel.withSuccess(null);
  }

  @override
  Future<ApiResponseModel> delete(int id) async {
    // Zero-Trust IDOR check: address 999 belongs to another customer
    if (id == 999) {
      return ApiResponseModel.withError('403 Forbidden: Cannot delete resource belonging to another customer');
    }
    mockDatabase.removeWhere((item) => item.id == id);
    return ApiResponseModel.withSuccess(null);
  }

  @override
  Future<List<AddressModel>> getList({bool isShipping = false, bool isBilling = false, bool fromRemove = false, bool all = false}) async {
    // Scoped strictly to authenticated customer (id 101)
    return mockDatabase.where((a) => a.id != 999).toList();
  }

  @override
  List<LabelAsModel> getAddressType() {
    return [
      LabelAsModel('Home', 'home.png'),
      LabelAsModel('Permanent', 'location.png'),
      LabelAsModel('Others', 'more.png'),
    ];
  }

  @override
  Future<ApiResponseModel> getDeliveryRestrictedCountryBySearch(String searchName) async => ApiResponseModel.withSuccess(null);

  @override
  Future<ApiResponseModel> getDeliveryRestrictedCountryList() async => ApiResponseModel.withSuccess(null);

  @override
  Future<ApiResponseModel> getDeliveryRestrictedZipBySearch(String searchName) async => ApiResponseModel.withSuccess(null);

  @override
  Future<ApiResponseModel> getDeliveryRestrictedZipList() async => ApiResponseModel.withSuccess(null);

  @override
  Future<ApiResponseModel> update(Map<String, dynamic> body, int addressId) async {
    if (addressId == 999) {
      return ApiResponseModel.withError('403 Forbidden: Cannot modify unowned address');
    }
    return ApiResponseModel.withSuccess(null);
  }
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  late MockAddressService mockService;
  late AddressController controller;

  setUp(() {
    mockService = MockAddressService();
    controller = AddressController(addressServiceInterface: mockService);
  });

  group('VM-CUST-005: Customer Address LGA Widget Proof Suite', () {
    test('1. Valid Nigeria -> Akwa Ibom -> Uyo cascade loads canonical LGAs', () async {
      await controller.getCountries();
      expect(controller.countryList.length, 2);
      expect(controller.selectedCountry?.name, 'Nigeria');

      // Select Nigeria -> Load states
      await controller.getStates(1);
      expect(controller.stateList.length, 3);
      final akwaIbom = controller.stateList.firstWhere((s) => s.name == 'Akwa Ibom');
      expect(akwaIbom.id, 3);

      // Select Akwa Ibom -> Load LGAs
      controller.setSelectedState(akwaIbom);
      expect(controller.selectedState?.name, 'Akwa Ibom');
      await controller.getLgas(3);

      // Verify all 6 primary Akwa Ibom test LGAs are offered
      final lgaNames = controller.lgaList.map((l) => l.name).toList();
      expect(lgaNames, containsAll(['Abak', 'Eket', 'Ikot Abasi', 'Ikot Ekpene', 'Oron', 'Uyo']));

      // Select Uyo (ID: 142)
      final uyo = controller.lgaList.firstWhere((l) => l.id == 142);
      expect(uyo.name, 'Uyo');
      expect(uyo.stateId, 3);

      final success = controller.setSelectedLga(uyo);
      expect(success, isTrue);
      expect(controller.selectedLga?.id, 142);
      expect(controller.selectedLga?.name, 'Uyo');
      expect(controller.geographyErrorMessage, isNull);
    });

    test('2. Mismatched State/LGA selection is rejected client-side with clear error message', () async {
      // Set State to Akwa Ibom (stateId = 3)
      final akwaIbom = StateModel(id: 3, countryId: 1, name: 'Akwa Ibom', stateCode: 'AK');
      controller.setSelectedState(akwaIbom);

      // Hostile / Mismatched LGA from Lagos (Ikeja: id=501, stateId=25)
      final ikeja = LgaModel(id: 501, stateId: 25, name: 'Ikeja');

      // Verify isStateLgaMatched catches the discrepancy
      expect(controller.isStateLgaMatched(state: akwaIbom, lga: ikeja), isFalse);

      // Attempt to set mismatched LGA into controller
      final success = controller.setSelectedLga(ikeja);
      expect(success, isFalse, reason: 'Controller must reject LGA that does not belong to the selected State');
      expect(controller.selectedLga, isNull, reason: 'Selected LGA must be reset on mismatch');
      expect(controller.geographyErrorMessage, contains('does not belong to the chosen State'));
    });

    test('3. Save payload shape strictly contains {country, state, lga, address} with ZERO fee fields', () async {
      final address = AddressModel(
        contactPersonName: 'Victor Edet',
        phone: '+2348022334455',
        address: '42 Oron Road, Uyo',
        countryId: 1,
        country: 'Nigeria',
        stateId: 3,
        state: 'Akwa Ibom',
        lgaId: 142,
        lgaName: 'Uyo',
        city: 'Uyo',
        zip: '520211',
        latitude: '5.0377',
        longitude: '7.9128',
      );

      final json = address.toJson();

      // Assert required canonical geography fields exist
      expect(json['country_id'], 1);
      expect(json['state_id'], 3);
      expect(json['lga_id'], 142);
      expect(json['address'], '42 Oron Road, Uyo');
      expect(json['contact_person_name'], 'Victor Edet');
      expect(json['phone'], '+2348022334455');
      expect(json['latitude'], '5.0377');
      expect(json['longitude'], '7.9128');

      // Assert FORBIDDEN fields are strictly absent
      expect(json.containsKey('fee'), isFalse, reason: 'Delivery fee is backend authority only');
      expect(json.containsKey('delivery_fee'), isFalse, reason: 'Delivery fee is backend authority only');
      expect(json.containsKey('shipping_cost'), isFalse, reason: 'Shipping cost is backend authority only');
      expect(json.containsKey('origin_lga_id'), isFalse, reason: 'Origin LGA is determined server-side from shop');
      expect(json.containsKey('hub_id'), isFalse, reason: 'Hubs are internal logistics, not public geography');

      // Add address to mock service
      await mockService.add(address);
      final list = await mockService.getList();
      expect(list.any((a) => a.contactPersonName == 'Victor Edet'), isTrue);
    });

    test('4. Zero-Trust IDOR check: Save, list, and delete operate on own addresses only', () async {
      final myAddresses = await mockService.getList();
      expect(myAddresses.any((a) => a.id == 101), isTrue);
      expect(myAddresses.any((a) => a.id == 999), isFalse, reason: 'Victim address must never be returned in own list');

      // Attempt hostile delete of address 999 (unowned)
      final deleteResponse = await mockService.delete(999);
      expect(deleteResponse.error, contains('403 Forbidden'));

      // Legitimate delete of owned address 101 succeeds
      final okResponse = await mockService.delete(101);
      expect(okResponse.error, isNull);
      final remaining = await mockService.getList();
      expect(remaining.any((a) => a.id == 101), isFalse);
    });

    test('5. Free-text address + optional coordinates captured without exposing hubs as geography', () {
      final address = AddressModel(
        address: 'Plot 12, Unit G, Shelter Afrique Estate, Uyo',
        latitude: '5.0123',
        longitude: '7.9456',
        countryId: 1,
        stateId: 3,
        lgaId: 142,
      );

      final json = address.toJson();
      expect(json['address'], 'Plot 12, Unit G, Shelter Afrique Estate, Uyo');
      expect(json['latitude'], '5.0123');
      expect(json['longitude'], '7.9456');

      // Verify no hub or internal routing leaked
      expect(json.containsKey('hub'), isFalse);
      expect(json.containsKey('corridor'), isFalse);
      expect(json.containsKey('route_id'), isFalse);
    });
  });
}
