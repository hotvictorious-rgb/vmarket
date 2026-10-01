# Independent Adversarial Review — VM-CUST-005

Ticket:                   VM-CUST-005 — Customer Address LGA Widget Proof — Country-State-LGA Cascade
Reviewer:                 AI-5 (Customer Domain Independent Reviewer)
Model/tool used:          Vic / DeepMind Agent
Commit reviewed:          ca23e6e940d9c72be6de287ec20c815cfdd6abab
Review cycle number:      1
Files reviewed:
  - User app/test/features/address/address_cascade_widget_test.dart
  - User app/lib/features/address/screens/add_new_address_screen.dart (read-only)
  - User app/lib/features/address/controllers/address_controller.dart (read-only)
Tests run by reviewer:
  - Direct flutter test blocked by missing symlink support (Developer Mode) in this sandbox.

Business-rule findings:
  - FAIL: The ticket requirements state: "Mismatched State/LGA selection is rejected client-side with clear message". At commit ca23e6e9, the test proves this by inventing its own synthetic mismatch guard `_onSave` inside the test harness (`AddressCascadeHarness`). It verifies `controller.selectedLga!.stateId != controller.selectedState!.id` purely within the test environment.
  - FAIL: Production code in `User app/lib/features/address/screens/add_new_address_screen.dart` lines 569-600 enforces that both State and LGA are not null, but **does not verify** that the selected LGA belongs to the selected State before building the AddressModel.

Security findings:
  - PASS: Foreign customer data separation works in the mocked FakeAddressService (foreign address deletion forbidden).

Prompt-injection / untrusted-input findings:
  - No new prompt-injection surface.

Frontend findings:
  - FAIL: Test file path violation. The test was incorrectly created at `User app/test/features/address/address_cascade_widget_test.dart` instead of the mandated `User app/test/address_lga_test.dart` required by VM-CUST-005.md.
  - FAIL: Production frontend missing required validation logic in `AddNewAddressScreen.dart` (or via `AddressController.dart`).

Backend findings:
  - Not applicable (Frontend task only).

Integration findings:
  - See Business-rule findings. The mismatch guard is mocked and nonexistent in actual production paths.

Client compatibility findings:
  - No mobile API changes; pure internal client enforcement of canonical geography mappings.

Testing findings:
  - FAIL: Synthetic passing test. The test `AddressCascadeHarness` checks an enforcement mechanism that doesn't mirror `AddNewAddressScreen`. Tests must validate existing/production screen widgets, or a refactored production screen that handles the _onSave accurately.

Performance findings:
  - N/A.

Dependency findings:
  - None modified.

Privacy / data-impact findings:
  - N/A.

Design / localization findings:
  - The mismatch message `"Selected LGA does not belong to the selected State"` is statically defined in the test file but not invoked natively via `getTranslated`.

Blockers:
  1. **Synthetic Mismatch Guard**: The state/LGA mismatch guard was implemented in the test harness rather than production code. Production code in `AddNewAddressScreen.dart` creates `AddressModel` from potentially mismatched values. You must move the State/LGA mismatch guard into `AddressController` or `AddNewAddressScreen`.
  2. **Wrong Test Path**: The test file was placed in `User app/test/features/address/address_cascade_widget_test.dart`. You must move or rename it to `User app/test/address_lga_test.dart` as explicitly requested by VM-CUST-005.

Non-blockers:
  None.

Decision:                 CHANGES_REQUIRED
