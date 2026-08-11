import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_app_bar_widget.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_button_widget.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_snackbar_widget.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/textfeild/custom_text_feild_widget.dart';
import 'package:sixvalley_vendor_app/features/auth/controllers/auth_controller.dart';
import 'package:sixvalley_vendor_app/features/bank_info/controllers/bank_info_controller.dart';
import 'package:sixvalley_vendor_app/features/bank_info/widgets/bank_selection_bottom_sheet.dart';
import 'package:sixvalley_vendor_app/features/bank_info/widgets/verify_bank_otp_sheet.dart';
import 'package:sixvalley_vendor_app/features/profile/controllers/profile_controller.dart';
import 'package:sixvalley_vendor_app/features/profile/domain/models/profile_body.dart';
import 'package:sixvalley_vendor_app/features/profile/domain/models/profile_info.dart';
import 'package:sixvalley_vendor_app/localization/language_constrants.dart';
import 'package:sixvalley_vendor_app/utill/color_resources.dart';
import 'package:sixvalley_vendor_app/utill/dimensions.dart';
import 'package:sixvalley_vendor_app/utill/styles.dart';

class BankEditingScreen extends StatefulWidget {
  final ProfileInfoModel? sellerModel;
  const BankEditingScreen({super.key, required this.sellerModel});

  @override
  BankEditingScreenState createState() => BankEditingScreenState();
}

class BankEditingScreenState extends State<BankEditingScreen> {
  TextEditingController? _bankNameController;
  TextEditingController? _branchController;
  TextEditingController? _holderNameController;
  TextEditingController? _accountController;
  final FocusNode _bankNameNode = FocusNode();
  final FocusNode _branchNode = FocusNode();
  final FocusNode _holderNameNode = FocusNode();
  final FocusNode _accountNode = FocusNode();
  GlobalKey<FormState>? _formKeyLogin;

  final GlobalKey<ScaffoldState> _scaffoldKey = GlobalKey<ScaffoldState>();

  @override
  void initState() {
    super.initState();
    _formKeyLogin = GlobalKey<FormState>();
    _bankNameController = TextEditingController();
    _branchController = TextEditingController();
    _holderNameController = TextEditingController();
    _accountController = TextEditingController();

    _bankNameController!.text = widget.sellerModel?.bankName ?? '';
    _branchController!.text = widget.sellerModel?.branch ?? 'Head Office';
    _holderNameController!.text = widget.sellerModel?.holderName ?? '';
    _accountController!.text = widget.sellerModel?.accountNo ?? '';

    WidgetsBinding.instance.addPostFrameCallback((_) {
      final bankController = Provider.of<BankInfoController>(context, listen: false);
      bankController.fetchNigerianBanks();
    });
  }

  @override
  void dispose() {
    _bankNameController!.dispose();
    _branchController!.dispose();
    _holderNameController!.dispose();
    _accountController!.dispose();
    super.dispose();
  }

  void _onAccountChanged(String value) {
    if (value.length == 10) {
      final bankController = Provider.of<BankInfoController>(context, listen: false);
      bankController.resolveAccountNumber(value).then((_) {
        if (bankController.isAccountResolved && bankController.resolvedAccountName != null) {
          setState(() {
            _holderNameController!.text = bankController.resolvedAccountName!;
          });
        }
      });
    }
  }

  Future<void> _submitBankDetails({String? otp}) async {
    final bankController = Provider.of<BankInfoController>(context, listen: false);
    final profileController = Provider.of<ProfileController>(context, listen: false);
    final authController = Provider.of<AuthController>(context, listen: false);

    ProfileInfoModel updateUserInfoModel = bankController.bankInfo ?? widget.sellerModel!;
    updateUserInfoModel.bankName = _bankNameController?.text ?? "";
    updateUserInfoModel.branch = _branchController?.text ?? "Head Office";
    updateUserInfoModel.holderName = _holderNameController?.text ?? '';
    updateUserInfoModel.accountNo = _accountController?.text ?? '';

    ProfileInfoModel userInfo = profileController.userInfoModel!;
    ProfileBody sellerBody = ProfileBody(
      sMethod: '_put',
      fName: userInfo.fName,
      lName: userInfo.lName,
      image: userInfo.image,
      bankName: _bankNameController?.text ?? "",
      branch: _branchController?.text ?? "Head Office",
      accountNo: _accountController?.text ?? '',
      holderName: _holderNameController?.text ?? '',
    );

    final response = await bankController.updateBankInfo(
      context,
      updateUserInfoModel,
      sellerBody,
      authController.getUserToken(),
      otp: otp,
    );

    if (!response.isSuccess) {
      showCustomSnackBarWidget(response.message, context, isError: true);
    } else {
      profileController.getSellerInfo();
    }
  }

  Future<void> _handleSave() async {
    String bankName = _bankNameController!.text.trim();
    String branchName = _branchController!.text.trim();
    String holderName = _holderNameController!.text.trim();
    String account = _accountController!.text.trim();

    final bankController = Provider.of<BankInfoController>(context, listen: false);
    final existingBank = bankController.bankInfo ?? widget.sellerModel;

    if (existingBank != null &&
        existingBank.bankName == bankName &&
        existingBank.accountNo == account &&
        existingBank.holderName == holderName) {
      showCustomSnackBarWidget(
        getTranslated('change_something', context) ?? 'No changes made to bank info.',
        context,
      );
      return;
    }

    if (bankName.isEmpty) {
      showCustomSnackBarWidget(
        getTranslated('enter_bank_name', context) ?? 'Please select a bank',
        context,
      );
      return;
    }

    if (account.length != 10) {
      showCustomSnackBarWidget(
        getTranslated('enter_valid_account_no', context) ?? 'Please enter a valid 10-digit NUBAN account number',
        context,
      );
      return;
    }

    if (holderName.isEmpty) {
      showCustomSnackBarWidget(
        getTranslated('enter_holder_name', context) ?? 'Please verify account name',
        context,
      );
      return;
    }

    // If seller already has an account number saved, trigger Email OTP
    bool isModifyingExisting = existingBank?.accountNo != null && existingBank!.accountNo!.isNotEmpty;
    if (isModifyingExisting) {
      final otpResponse = await bankController.sendBankOtp(bankName, account, holderName);
      if (otpResponse.isSuccess) {
        showModalBottomSheet(
          context: context,
          isScrollControlled: true,
          backgroundColor: Colors.transparent,
          builder: (ctx) => VerifyBankOtpSheet(
            bankName: bankName,
            accountNo: account,
            holderName: holderName,
            onOtpVerified: (otp) {
              _submitBankDetails(otp: otp);
            },
          ),
        );
      } else {
        showCustomSnackBarWidget(otpResponse.message, context, isError: true);
      }
    } else {
      // First time adding bank details
      _submitBankDetails();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      key: _scaffoldKey,
      appBar: CustomAppBarWidget(title: getTranslated('bank_info', context) ?? 'Bank Information'),
      body: Consumer<BankInfoController>(
        builder: (context, bankController, child) {
          return Padding(
            padding: const EdgeInsets.all(Dimensions.paddingSizeLarge),
            child: Form(
              key: _formKeyLogin,
              child: ListView(
                physics: const BouncingScrollPhysics(),
                children: [
                  // Security Notice Banner
                  Container(
                    padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
                    decoration: BoxDecoration(
                      color: Theme.of(context).primaryColor.withValues(alpha: 0.08),
                      borderRadius: BorderRadius.circular(Dimensions.paddingSizeSmall),
                      border: Border.all(color: Theme.of(context).primaryColor.withValues(alpha: 0.3)),
                    ),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Icon(Icons.shield_outlined, color: Theme.of(context).primaryColor, size: 22),
                        const SizedBox(width: Dimensions.paddingSizeSmall),
                        Expanded(
                          child: Text(
                            getTranslated('bank_security_notice', context) ??
                                'For security against unauthorized changes, bank details require Email OTP authorization and can only be modified once every 48 hours.',
                            style: robotoRegular.copyWith(
                              fontSize: Dimensions.fontSizeSmall,
                              color: Theme.of(context).textTheme.bodyLarge?.color,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: Dimensions.paddingSizeLarge),

                  // Bank Selector
                  Text(
                    getTranslated('bank_name', context) ?? 'Select Bank',
                    style: titilliumRegular.copyWith(
                      fontSize: Dimensions.fontSizeDefault,
                      color: Theme.of(context).hintColor,
                    ),
                  ),
                  const SizedBox(height: Dimensions.paddingSizeSmall),
                  InkWell(
                    onTap: () {
                      showModalBottomSheet(
                        context: context,
                        isScrollControlled: true,
                        backgroundColor: Colors.transparent,
                        builder: (ctx) => BankSelectionBottomSheet(
                          onBankSelected: (bank) {
                            bankController.selectBank(bank);
                            setState(() {
                              _bankNameController!.text = bank.name ?? '';
                            });
                            if (_accountController!.text.length == 10) {
                              _onAccountChanged(_accountController!.text);
                            }
                          },
                        ),
                      );
                    },
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
                      decoration: BoxDecoration(
                        border: Border.all(color: Theme.of(context).hintColor.withValues(alpha: 0.5)),
                        borderRadius: BorderRadius.circular(Dimensions.paddingSizeSmall),
                      ),
                      child: Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          Expanded(
                            child: Text(
                              _bankNameController!.text.isNotEmpty
                                  ? _bankNameController!.text
                                  : (getTranslated('select_bank', context) ?? 'Select Nigerian Bank'),
                              style: robotoRegular.copyWith(
                                color: _bankNameController!.text.isNotEmpty
                                    ? Theme.of(context).textTheme.bodyLarge?.color
                                    : Theme.of(context).hintColor,
                              ),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          const Icon(Icons.arrow_drop_down, color: Colors.grey),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: Dimensions.paddingSizeLarge),

                  // Account Number
                  Text(
                    getTranslated('account_no', context) ?? '10-Digit NUBAN Account Number',
                    style: titilliumRegular.copyWith(
                      fontSize: Dimensions.fontSizeDefault,
                      color: Theme.of(context).hintColor,
                    ),
                  ),
                  const SizedBox(height: Dimensions.paddingSizeSmall),
                  CustomTextFieldWidget(
                    border: true,
                    hintText: '0123456789',
                    controller: _accountController,
                    focusNode: _accountNode,
                    textInputAction: TextInputAction.next,
                    textInputType: TextInputType.number,
                    onChanged: _onAccountChanged,
                  ),
                  const SizedBox(height: Dimensions.paddingSizeSmall),

                  // Resolution Indicator
                  if (bankController.isResolvingAccount)
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 6),
                      child: Row(
                        children: [
                          const SizedBox(
                            width: 16,
                            height: 16,
                            child: CircularProgressIndicator(strokeWidth: 2),
                          ),
                          const SizedBox(width: 8),
                          Text(
                            getTranslated('verifying_account_with_bank', context) ?? 'Verifying account with bank...',
                            style: robotoRegular.copyWith(fontSize: Dimensions.fontSizeSmall, color: Theme.of(context).hintColor),
                          ),
                        ],
                      ),
                    )
                  else if (bankController.isAccountResolved && bankController.resolvedAccountName != null)
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 6),
                      child: Row(
                        children: [
                          const Icon(Icons.check_circle, color: Colors.green, size: 18),
                          const SizedBox(width: 6),
                          Expanded(
                            child: Text(
                              'Verified: ${bankController.resolvedAccountName}',
                              style: robotoBold.copyWith(fontSize: Dimensions.fontSizeSmall, color: Colors.green),
                            ),
                          ),
                        ],
                      ),
                    )
                  else if (bankController.accountResolutionError != null)
                    Padding(
                      padding: const EdgeInsets.symmetric(vertical: 6),
                      child: Row(
                        children: [
                          Icon(Icons.error_outline, color: Theme.of(context).colorScheme.error, size: 18),
                          const SizedBox(width: 6),
                          Expanded(
                            child: Text(
                              bankController.accountResolutionError!,
                              style: robotoRegular.copyWith(
                                fontSize: Dimensions.fontSizeSmall,
                                color: Theme.of(context).colorScheme.error,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),

                  const SizedBox(height: Dimensions.paddingSizeSmall),

                  // Account Holder Name
                  Text(
                    getTranslated('holder_name', context) ?? 'Account Holder Name',
                    style: titilliumRegular.copyWith(
                      fontSize: Dimensions.fontSizeDefault,
                      color: Theme.of(context).hintColor,
                    ),
                  ),
                  const SizedBox(height: Dimensions.paddingSizeSmall),
                  CustomTextFieldWidget(
                    border: true,
                    hintText: 'Auto-resolved from bank',
                    controller: _holderNameController,
                    focusNode: _holderNameNode,
                    textInputAction: TextInputAction.done,
                    textInputType: TextInputType.text,
                  ),

                  const SizedBox(height: Dimensions.paddingSizeLarge),

                  // Save Button
                  Container(
                    margin: const EdgeInsets.symmetric(vertical: Dimensions.paddingSizeSmall),
                    child: !bankController.isLoading
                        ? CustomButtonWidget(
                            onTap: _handleSave,
                            btnTxt: getTranslated('save_bank_info', context) ?? 'Save Bank Information',
                          )
                        : Center(
                            child: CircularProgressIndicator(
                              valueColor: AlwaysStoppedAnimation<Color>(Theme.of(context).primaryColor),
                            ),
                          ),
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

