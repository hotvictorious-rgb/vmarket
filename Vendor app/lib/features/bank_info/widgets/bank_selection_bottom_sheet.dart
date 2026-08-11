import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:sixvalley_vendor_app/features/bank_info/controllers/bank_info_controller.dart';
import 'package:sixvalley_vendor_app/features/bank_info/domain/models/bank_model.dart';
import 'package:sixvalley_vendor_app/localization/language_constrants.dart';
import 'package:sixvalley_vendor_app/utill/dimensions.dart';
import 'package:sixvalley_vendor_app/utill/styles.dart';

class BankSelectionBottomSheet extends StatefulWidget {
  final Function(BankModel) onBankSelected;
  const BankSelectionBottomSheet({super.key, required this.onBankSelected});

  @override
  State<BankSelectionBottomSheet> createState() => _BankSelectionBottomSheetState();
}

class _BankSelectionBottomSheetState extends State<BankSelectionBottomSheet> {
  final TextEditingController _searchController = TextEditingController();
  List<BankModel> _filteredBanks = [];

  @override
  void initState() {
    super.initState();
    final bankController = Provider.of<BankInfoController>(context, listen: false);
    _filteredBanks = bankController.nigerianBanks;
  }

  void _filterBanks(String query, List<BankModel> allBanks) {
    setState(() {
      if (query.isEmpty) {
        _filteredBanks = allBanks;
      } else {
        _filteredBanks = allBanks
            .where((bank) => (bank.name ?? '').toLowerCase().contains(query.toLowerCase()))
            .toList();
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      height: MediaQuery.of(context).size.height * 0.75,
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(Dimensions.paddingSizeDefault)),
      ),
      child: Column(
        children: [
          Container(
            margin: const EdgeInsets.only(top: 8, bottom: 12),
            height: 4,
            width: 40,
            decoration: BoxDecoration(
              color: Theme.of(context).hintColor.withValues(alpha: 0.3),
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  getTranslated('select_bank', context) ?? 'Select Your Bank',
                  style: robotoBold.copyWith(fontSize: Dimensions.fontSizeLarge),
                ),
                IconButton(
                  onPressed: () => Navigator.pop(context),
                  icon: const Icon(Icons.close),
                ),
              ],
            ),
          ),
          Padding(
            padding: const EdgeInsets.all(Dimensions.paddingSizeDefault),
            child: TextField(
              controller: _searchController,
              onChanged: (val) {
                final bankController = Provider.of<BankInfoController>(context, listen: false);
                _filterBanks(val, bankController.nigerianBanks);
              },
              decoration: InputDecoration(
                hintText: getTranslated('search_bank', context) ?? 'Search bank name (e.g. GTBank, OPay, Zenith)...',
                prefixIcon: const Icon(Icons.search),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(Dimensions.paddingSizeSmall),
                  borderSide: BorderSide(color: Theme.of(context).primaryColor),
                ),
                contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              ),
            ),
          ),
          Expanded(
            child: Consumer<BankInfoController>(
              builder: (context, bankController, child) {
                if (bankController.isLoadingBanks) {
                  return const Center(child: CircularProgressIndicator());
                }

                if (_filteredBanks.isEmpty) {
                  return Center(
                    child: Text(
                      getTranslated('no_bank_found', context) ?? 'No bank found matching query',
                      style: robotoRegular.copyWith(color: Theme.of(context).hintColor),
                    ),
                  );
                }

                return ListView.separated(
                  padding: const EdgeInsets.symmetric(horizontal: Dimensions.paddingSizeDefault),
                  itemCount: _filteredBanks.length,
                  separatorBuilder: (context, index) => const Divider(height: 1),
                  itemBuilder: (context, index) {
                    final bank = _filteredBanks[index];
                    final isSelected = bankController.selectedBank?.code == bank.code;

                    return ListTile(
                      contentPadding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                      title: Text(
                        bank.name ?? '',
                        style: robotoMedium.copyWith(
                          color: isSelected ? Theme.of(context).primaryColor : null,
                          fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                        ),
                      ),
                      trailing: isSelected
                          ? Icon(Icons.check_circle, color: Theme.of(context).primaryColor)
                          : null,
                      onTap: () {
                        widget.onBankSelected(bank);
                        Navigator.pop(context);
                      },
                    );
                  },
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}
