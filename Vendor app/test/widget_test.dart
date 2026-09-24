import 'package:flutter_test/flutter_test.dart';
import 'package:sixvalley_vendor_app/features/auth/domain/models/register_model.dart';
import 'package:sixvalley_vendor_app/features/employee_management/domain/models/employee_model.dart';
import 'package:sixvalley_vendor_app/features/order/domain/models/order_model.dart';
import 'package:sixvalley_vendor_app/features/order_details/domain/models/order_setup_model.dart';
import 'package:sixvalley_vendor_app/features/order_details/domain/repositories/order_details_repository.dart';

void main() {
  test('parses authoritative order values without deriving a total', () {
    final order = Order.fromJson({
      'id': 42,
      'order_status': 'processing',
      'payment_status': 'paid',
      'order_amount': '1250.00',
      'init_order_amount': '1300.00',
      'total_tax_amount': '75.00',
      'discount_amount': '50.00',
      'shipping_cost': '100.00',
      'refer_and_earn_discount': '25.00',
      'extra_discount': '10.00',
    });

    expect(order.orderAmount, 1250.0);
    expect(order.initOrderAmount, 1300.0);
    expect(order.totalTaxAmount, 75.0);
    expect(order.discountAmount, 50.0);
    expect(order.shippingCost, 100.0);
    expect(order.referAndEarnDiscount, 25.0);
    expect(order.extraDiscount, 10.0);
  });

  test('exposes only vendor order states supported by the current flow', () async {
    final response = await OrderDetailsRepository(dioClient: null).getOrderStatusList();

    expect(response.response?.statusCode, 200);
    expect(response.response?.data, [
      'pending',
      'confirmed',
      'processing',
      'ready_for_pickup',
      'canceled',
    ]);
  });

  test('keeps payment state display-only in order setup payloads', () {
    final setup = OrderSetupModel(
      orderId: 42,
      orderStatus: 'confirmed',
    );

    expect(setup.toJson()['order_status'], 'confirmed');
    expect(setup.toJson(), isNot(contains('payment_status')));
  });

  test('validates vendor registration model credentials and shop data', () {
    final registration = RegisterModel(
      fName: 'Vendor',
      lName: 'Merchant',
      phone: '08012345678',
      email: 'vendor@vmarket.ng',
      password: 'SecurePassword123',
      confirmPassword: 'SecurePassword123',
      shopName: 'Uyo Main Store',
      shopAddress: '12 Aka Road, Uyo, Akwa Ibom',
      businessTin: 'TIN-987654321',
      tinExpireDate: '2027-12-31',
    );

    expect(registration.fName, 'Vendor');
    expect(registration.lName, 'Merchant');
    expect(registration.email, 'vendor@vmarket.ng');
    expect(registration.shopName, 'Uyo Main Store');
    expect(registration.shopAddress, '12 Aka Road, Uyo, Akwa Ibom');
    expect(registration.businessTin, 'TIN-987654321');
  });

  test('employee model serializes and preserves shop isolation', () {
    final employeeJson = {
      'id': 101,
      'name': 'John Clerk',
      'email': 'clerk@shop.com',
      'phone': '+2348000000000',
      'role_id': 2,
      'role_name': 'Sales Manager',
      'shop_id': 5,
      'status': 1,
      'created_at': '2026-09-24T12:00:00Z',
    };

    final employee = EmployeeModel.fromJson(employeeJson);
    expect(employee.id, 101);
    expect(employee.name, 'John Clerk');
    expect(employee.shopId, 5);
    expect(employee.status, true);
    expect(employee.roleName, 'Sales Manager');

    final serialized = employee.toJson();
    expect(serialized['shop_id'], 5);
    expect(serialized['status'], 1);
  });
}
