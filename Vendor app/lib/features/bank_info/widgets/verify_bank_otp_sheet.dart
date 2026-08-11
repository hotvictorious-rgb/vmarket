import 'dart:async';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_button_widget.dart';
import 'package:sixvalley_vendor_app/common/basewidgets/custom_snackbar_widget.dart';
import 'package:sixvalley_vendor_app/features/bank_info/controllers/bank_info_controller.dart';
import 'package:sixvalley_vendor_app/localization/language_constrants.dart';
import 'package:sixvalley_vendor_app/utill/dimensions.dart';
import 'package:sixvalley_vendor_app/utill/styles.dart';

class VerifyBankOtpSheet extends StatefulWidget {
  final String bankName;
  final String accountNo;
  final String holderName;
  final Function(String otp) onOtpVerified;

  const VerifyBankOtpSheet({
    super.key,
    required this.bankName,
    required this.accountNo,
    required this.holderName,
    required this.onOtpVerified,
  });

  @override
  State<VerifyBankOtpSheet> createState() => _VerifyBankOtpSheetState();
}

class _VerifyBankOtpSheetState extends State<VerifyBankOtpSheet> {
  final TextEditingController _otpController = TextEditingController();
  int _secondsRemaining = 120;
  Timer? _timer;
  bool _canResend = false;

  @override
  void initState() {
    super.initState();
    _startTimer();
  }

  void _startTimer() {
    _secondsRemaining = 120;
    _canResend = false;
    _timer?.cancel();
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_secondsRemaining > 0) {
        setState(() {
          _secondsRemaining--;
        });
      } else {
        setState(() {
          _canResend = true;
        });
        timer.cancel();
      }
    });
  }

  @override
  void dispose() {
    _timer?.cancel();
    _otpController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        bottom: MediaQuery.of(context).viewInsets.bottom,
      ),
      child: Container(
        padding: const EdgeInsets.all(Dimensions.paddingSizeLarge),
        decoration: BoxDecoration(
          color: Theme.of(context).cardColor,
          borderRadius: const BorderRadius.vertical(top: Radius.circular(Dimensions.paddingSizeDefault)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            Container(
              height: 4,
              width: 40,
              decoration: BoxDecoration(
                color: Theme.of(context).hintColor.withValues(alpha: 0.3),
                borderRadius: BorderRadius.circular(2),
              ),
            ),
            const SizedBox(height: Dimensions.paddingSizeLarge),
            Icon(
              Icons.mark_email_read_outlined,
              size: 50,
              color: Theme.of(context).primaryColor,
            ),
            const SizedBox(height: Dimensions.paddingSizeSmall),
            Text(
              getTranslated('security_verification', context) ?? 'Security Verification',
              style: robotoBold.copyWith(fontSize: Dimensions.fontSizeLarge),
            ),
            const SizedBox(height: Dimensions.paddingSizeExtraSmall),
            Text(
              getTranslated('bank_otp_sent_instruction', context) ??
                  'A 6-digit security code was sent to your registered email to authorize this bank account change.',
              textAlign: TextAlign.center,
              style: robotoRegular.copyWith(
                color: Theme.of(context).hintColor,
                fontSize: Dimensions.fontSizeSmall,
              ),
            ),
            const SizedBox(height: Dimensions.paddingSizeLarge),
            TextField(
              controller: _otpController,
              keyboardType: TextInputType.number,
              maxLength: 6,
              textAlign: TextAlign.center,
              style: robotoBold.copyWith(
                fontSize: Dimensions.fontSizeExtraLarge,
                letterSpacing: 8,
              ),
              decoration: InputDecoration(
                counterText: '',
                hintText: '------',
                hintStyle: TextStyle(letterSpacing: 8, color: Theme.of(context).hintColor),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(Dimensions.paddingSizeSmall),
                  borderSide: BorderSide(color: Theme.of(context).primaryColor),
                ),
                contentPadding: const EdgeInsets.symmetric(vertical: 12),
              ),
            ),
            const SizedBox(height: Dimensions.paddingSizeDefault),
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  _canResend
                      ? (getTranslated('didnt_receive_code', context) ?? "Didn't receive code? ")
                      : '${getTranslated('resend_code_in', context) ?? 'Resend code in'} ${_secondsRemaining}s',
                  style: robotoRegular.copyWith(color: Theme.of(context).hintColor, fontSize: Dimensions.fontSizeSmall),
                ),
                if (_canResend)
                  TextButton(
                    onPressed: () async {
                      final bankController = Provider.of<BankInfoController>(context, listen: false);
                      final response = await bankController.sendBankOtp(
                        widget.bankName,
                        widget.accountNo,
                        widget.holderName,
                      );
                      if (response.isSuccess) {
                        _startTimer();
                        showCustomSnackBarWidget(
                          response.message,
                          context,
                          isToaster: true,
                          isError: false,
                        );
                      } else {
                        showCustomSnackBarWidget(
                          response.message,
                          context,
                          isToaster: true,
                          isError: true,
                        );
                      }
                    },
                    child: Text(
                      getTranslated('resend', context) ?? 'Resend OTP',
                      style: robotoBold.copyWith(color: Theme.of(context).primaryColor),
                    ),
                  ),
              ],
            ),
            const SizedBox(height: Dimensions.paddingSizeLarge),
            Consumer<BankInfoController>(
              builder: (context, bankController, child) {
                return bankController.isLoading
                    ? const Center(child: CircularProgressIndicator())
                    : CustomButtonWidget(
                        btnTxt: getTranslated('verify_and_update', context) ?? 'Verify & Update Bank',
                        onTap: () {
                          final otp = _otpController.text.trim();
                          if (otp.length != 6) {
                            showCustomSnackBarWidget(
                              getTranslated('enter_valid_6_digit_otp', context) ?? 'Please enter the 6-digit OTP code',
                              context,
                            );
                            return;
                          }
                          Navigator.pop(context);
                          widget.onOtpVerified(otp);
                        },
                      );
              },
            ),
            const SizedBox(height: Dimensions.paddingSizeSmall),
          ],
        ),
      ),
    );
  }
}
