import 'package:flutter/material.dart';
import 'package:flutter_sixvalley_ecommerce/features/location/controllers/location_controller.dart';
import 'package:flutter_sixvalley_ecommerce/localization/language_constrants.dart';
import 'package:flutter_sixvalley_ecommerce/utill/custom_themes.dart';
import 'package:flutter_sixvalley_ecommerce/utill/dimensions.dart';
import 'package:provider/provider.dart';

class ChooseLocationBottomSheetWidget extends StatefulWidget {
  final VoidCallback? onLocationChanged;
  const ChooseLocationBottomSheetWidget({super.key, this.onLocationChanged});

  @override
  State<ChooseLocationBottomSheetWidget> createState() => _ChooseLocationBottomSheetWidgetState();
}

class _ChooseLocationBottomSheetWidgetState extends State<ChooseLocationBottomSheetWidget> {
  final TextEditingController _searchController = TextEditingController();
  String _selectedLga = 'Uyo';
  int _selectedLgaId = 142;
  String _selectedState = 'Akwa Ibom';

  // Canonical Akwa Ibom & regional LGAs
  static const List<Map<String, dynamic>> _allLgas = [
    {'name': 'Uyo', 'id': 142, 'state': 'Akwa Ibom'},
    {'name': 'Eket', 'id': 125, 'state': 'Akwa Ibom'},
    {'name': 'Ikot Ekpene', 'id': 133, 'state': 'Akwa Ibom'},
    {'name': 'Oron', 'id': 140, 'state': 'Akwa Ibom'},
    {'name': 'Abak', 'id': 118, 'state': 'Akwa Ibom'},
    {'name': 'Ikot Abasi', 'id': 132, 'state': 'Akwa Ibom'},
    {'name': 'Ibiono Ibom', 'id': 128, 'state': 'Akwa Ibom'},
    {'name': 'Itu', 'id': 137, 'state': 'Akwa Ibom'},
    {'name': 'Nsit Ubium', 'id': 139, 'state': 'Akwa Ibom'},
    {'name': 'Mkpat Enin', 'id': 138, 'state': 'Akwa Ibom'},
    {'name': 'Essien Udim', 'id': 126, 'state': 'Akwa Ibom'},
    {'name': 'Etinan', 'id': 127, 'state': 'Akwa Ibom'},
    {'name': 'Onna', 'id': 141, 'state': 'Akwa Ibom'},
    {'name': 'Ukanafun', 'id': 143, 'state': 'Akwa Ibom'},
    {'name': 'Uruan', 'id': 144, 'state': 'Akwa Ibom'},
    {'name': 'Calabar Municipal', 'id': 201, 'state': 'Cross River'},
    {'name': 'Port Harcourt', 'id': 301, 'state': 'Rivers'},
    {'name': 'Ikeja', 'id': 401, 'state': 'Lagos'},
    {'name': 'Abuja Municipal', 'id': 501, 'state': 'FCT'},
  ];

  static const List<String> _quickChips = [
    'Uyo', 'Eket', 'Ikot Ekpene', 'Oron', 'Abak', 'Ikot Abasi'
  ];

  List<Map<String, dynamic>> _filteredLgas = [];

  @override
  void initState() {
    super.initState();
    final locCtrl = Provider.of<LocationController>(context, listen: false);
    _selectedLga = locCtrl.activeLgaName;
    _selectedLgaId = locCtrl.activeLgaId;
    _selectedState = locCtrl.activeStateName;
    _filteredLgas = List.from(_allLgas);

    _searchController.addListener(() {
      final query = _searchController.text.trim().toLowerCase();
      setState(() {
        if (query.isEmpty) {
          _filteredLgas = List.from(_allLgas);
        } else {
          _filteredLgas = _allLgas
              .where((lga) => lga['name'].toString().toLowerCase().contains(query))
              .toList();
        }
      });
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _selectLga(String name, int id, String state) {
    setState(() {
      _selectedLga = name;
      _selectedLgaId = id;
      _selectedState = state;
    });
  }

  @override
  Widget build(BuildContext context) {
    final bottomInset = MediaQuery.of(context).viewInsets.bottom;

    return Container(
      decoration: BoxDecoration(
        color: Theme.of(context).cardColor,
        borderRadius: const BorderRadius.vertical(top: Radius.circular(24)),
      ),
      padding: EdgeInsets.fromLTRB(20, 16, 20, 20 + bottomInset),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Drag handle
          Center(
            child: Container(
              width: 44,
              height: 4,
              decoration: BoxDecoration(
                color: Colors.grey.withValues(alpha: 0.3),
                borderRadius: BorderRadius.circular(2),
              ),
            ),
          ),
          const SizedBox(height: 16),

          // Header Title
          Text(
            getTranslated('choose_your_location', context) ?? 'Choose your location',
            style: titleBold.copyWith(
              fontSize: 18,
              color: Theme.of(context).textTheme.bodyLarge?.color,
            ),
          ),
          const SizedBox(height: 4),

          // Subtitle
          Text(
            getTranslated('delivery_options_speed_may_vary', context) ??
                'Delivery options and delivery speeds may vary for different locations',
            style: textRegular.copyWith(
              fontSize: 12,
              color: Theme.of(context).hintColor,
            ),
          ),
          const SizedBox(height: 14),

          // Country Selector Pill (Nigeria)
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: BoxDecoration(
              color: const Color(0xFFF3E8FF), // Light purple tint
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: const Color(0xFFD8B4FE)),
            ),
            child: Row(
              children: [
                const Text('🇳🇬', style: TextStyle(fontSize: 18)),
                const SizedBox(width: 8),
                Text(
                  'Nigeria',
                  style: textBold.copyWith(
                    color: const Color(0xFF4A148C),
                    fontSize: 14,
                  ),
                ),
                const Spacer(),
                Text(
                  'Marketplace Active',
                  style: textRegular.copyWith(
                    color: const Color(0xFF6B21A8),
                    fontSize: 11,
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 14),

          // Search Input: "type your residential lga"
          Container(
            decoration: BoxDecoration(
              color: Theme.of(context).highlightColor.withValues(alpha: 0.4),
              borderRadius: BorderRadius.circular(14),
              border: Border.all(color: const Color(0xFF7E22CE).withValues(alpha: 0.3)),
            ),
            child: TextField(
              controller: _searchController,
              decoration: InputDecoration(
                hintText: getTranslated('type_your_residential_lga', context) ??
                    'type your residential lga',
                hintStyle: textRegular.copyWith(
                  color: Theme.of(context).hintColor,
                  fontSize: 13,
                ),
                prefixIcon: const Icon(Icons.search, color: Color(0xFF7E22CE), size: 20),
                border: InputBorder.none,
                contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
              ),
            ),
          ),
          const SizedBox(height: 12),

          // Quick Chips
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: _quickChips.map((chip) {
                final isSelected = _selectedLga.toLowerCase() == chip.toLowerCase();
                return Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: InkWell(
                    onTap: () {
                      final match = _allLgas.firstWhere(
                        (l) => l['name'].toString().toLowerCase() == chip.toLowerCase(),
                        orElse: () => {'name': chip, 'id': 142, 'state': 'Akwa Ibom'},
                      );
                      _selectLga(match['name'], match['id'], match['state']);
                    },
                    borderRadius: BorderRadius.circular(20),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                      decoration: BoxDecoration(
                        color: isSelected
                            ? const Color(0xFF4A148C)
                            : Theme.of(context).cardColor,
                        borderRadius: BorderRadius.circular(20),
                        border: Border.all(
                          color: isSelected
                              ? const Color(0xFFFFD700)
                              : Colors.grey.withValues(alpha: 0.3),
                          width: isSelected ? 1.5 : 1,
                        ),
                      ),
                      child: Text(
                        chip,
                        style: textBold.copyWith(
                          fontSize: 12,
                          color: isSelected
                              ? const Color(0xFFFFD700)
                              : Theme.of(context).textTheme.bodyLarge?.color,
                        ),
                      ),
                    ),
                  );
                }).toList(),
              ),
            ),
          ),
          const SizedBox(height: 14),

          // Dropdown List of Filtered LGAs
          ConstrainedBox(
            constraints: const BoxConstraints(maxHeight: 140),
            child: ListView.builder(
              shrinkWrap: true,
              itemCount: _filteredLgas.length,
              itemBuilder: (context, index) {
                final lga = _filteredLgas[index];
                final isSelected = lga['name'] == _selectedLga;
                return ListTile(
                  dense: true,
                  contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 0),
                  leading: Icon(
                    Icons.location_on,
                    size: 18,
                    color: isSelected ? const Color(0xFF7E22CE) : Colors.grey,
                  ),
                  title: Text(
                    '${lga['name']}, ${lga['state']}',
                    style: textRegular.copyWith(
                      fontSize: 13,
                      fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                      color: isSelected
                          ? const Color(0xFF4A148C)
                          : Theme.of(context).textTheme.bodyLarge?.color,
                    ),
                  ),
                  trailing: isSelected
                      ? const Icon(Icons.check_circle, color: Color(0xFF7E22CE), size: 18)
                      : null,
                  onTap: () => _selectLga(lga['name'], lga['id'], lga['state']),
                );
              },
            ),
          ),
          const SizedBox(height: 14),

          // Selected Preview Card
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFFFFF8E1), Color(0xFFFFECB3)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: const Color(0xFFFFD700), width: 1.2),
            ),
            child: Row(
              children: [
                const Icon(Icons.check_circle_outline, color: Color(0xFF4A148C), size: 20),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    '$_selectedLga, $_selectedState • ⚡ Pickup & Delivery Active',
                    style: textBold.copyWith(
                      color: const Color(0xFF4A148C),
                      fontSize: 12,
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // Done Action Button
          InkWell(
            onTap: () async {
              final locCtrl = Provider.of<LocationController>(context, listen: false);
              await locCtrl.setCustomerLga(
                lgaName: _selectedLga,
                lgaId: _selectedLgaId,
                stateName: _selectedState,
              );
              if (mounted) {
                Navigator.of(context).pop();
                widget.onLocationChanged?.call();
              }
            },
            child: Container(
              height: 48,
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF6A1B9A), Color(0xFF4A148C)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(14),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFF4A148C).withValues(alpha: 0.25),
                    blurRadius: 8,
                    offset: const Offset(0, 4),
                  ),
                ],
              ),
              child: Center(
                child: Text(
                  getTranslated('done', context) ?? 'Done',
                  style: textBold.copyWith(
                    color: const Color(0xFFFFD700),
                    fontSize: 16,
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
