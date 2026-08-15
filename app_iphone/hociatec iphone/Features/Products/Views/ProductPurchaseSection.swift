import SwiftUI

struct ProductAddToCartButton: View {
    let isLoading: Bool
    let isDisabled: Bool
    let label: String
    let action: () -> Void

    var body: some View {
        Button(action: action) {
            HStack {
                Spacer()
                if isLoading {
                    ProgressView()
                } else {
                    Text(label)
                        .fontWeight(.semibold)
                }
                Spacer()
            }
            .padding()
            .background(Color.teal.opacity(0.15))
            .foregroundStyle(.teal)
            .clipShape(RoundedRectangle(cornerRadius: 12))
        }
        .disabled(isLoading || isDisabled)
        .accessibilityLabel(label)
        .accessibilityAddTraits(.isButton)
    }
}

struct RentalConfigurationSummary: View {
    let rentalMonths: Int
    let rentalStartDateLabel: String
    let rentalEndDateLabel: String
    let configureAction: () -> Void

    var body: some View {
        VStack(alignment: .leading, spacing: 8) {
            Text("Location")
                .font(.headline)
            VStack(alignment: .leading, spacing: 4) {
                Text("Début: \(rentalStartDateLabel)")
                    .foregroundStyle(.secondary)
                Text("Fin estimée: \(rentalEndDateLabel)")
                    .foregroundStyle(.secondary)
                Text("\(rentalMonths) mois")
                    .fontWeight(.semibold)
            }
            Button("Configurer la location", action: configureAction)
                .buttonStyle(.bordered)
                .accessibilityHint("Ouvre la configuration de la période de location")
        }
    }
}

struct RentalConfigurationSheet: View {
    @Binding var rentalMonths: Int
    @Binding var rentalStartDate: Date
    let onCancel: () -> Void
    let onConfirm: () -> Void

    private var endDateLabel: String {
        guard let monthAnchor = Calendar.current.date(byAdding: .month, value: max(1, rentalMonths), to: rentalStartDate),
              let endDate = Calendar.current.date(byAdding: .day, value: -1, to: monthAnchor) else {
            return "-"
        }
        return DateFormatters.frDay.string(from: endDate)
    }

    var body: some View {
        NavigationStack {
            Form {
                Section("Période") {
                    VStack(alignment: .leading, spacing: 8) {
                        Text("Date de début")
                            .font(.headline)
                        LocalizedDatePicker(
                            date: $rentalStartDate,
                            displayedComponents: [.date],
                            minimumDate: Calendar.current.startOfDay(for: Date()),
                            style: .inline
                        )
                        .frame(maxWidth: .infinity, minHeight: 320)
                    }
                    Stepper(value: $rentalMonths, in: 1...36) {
                        Text("Durée: \(rentalMonths) mois")
                    }
                    LabeledContent("Date de fin estimée", value: endDateLabel)
                }
            }
            .navigationTitle("Configurer la location")
            .navigationBarTitleDisplayMode(.inline)
            .toolbar {
                ToolbarItem(placement: .cancellationAction) {
                    Button("Annuler", action: onCancel)
                }
                ToolbarItem(placement: .confirmationAction) {
                    Button("Valider", action: onConfirm)
                }
            }
        }
    }
}

struct ProductQuantityControls: View {
    let currentQuantity: Int
    let canIncrease: Bool
    let isLoading: Bool
    let decreaseAction: () -> Void
    let increaseAction: () -> Void

    var body: some View {
        HStack(spacing: 16) {
            Button(action: decreaseAction) {
                Image(systemName: "minus")
                    .frame(width: 44, height: 44)
            }
            .buttonStyle(.bordered)
            .accessibilityLabel("Moins")

            Text("Quantité: \(currentQuantity)")
                .fontWeight(.semibold)

            if canIncrease {
                Button(action: increaseAction) {
                    Image(systemName: "plus")
                        .frame(width: 44, height: 44)
                }
                .buttonStyle(.bordered)
                .accessibilityLabel("Plus")
                .disabled(isLoading)
                .allowsHitTesting(!isLoading)
            }
        }
    }
}

struct ProductPurchaseSection: View {
    let currentQuantity: Int
    let stockLimit: Int
    let isLoading: Bool
    let isOutOfStock: Bool
    let addButtonLabel: String
    let showRentalSelector: Bool
    let rentalMonths: Int
    let rentalStartDateLabel: String
    let rentalEndDateLabel: String
    let decreaseRentalMonths: () -> Void
    let increaseRentalMonths: () -> Void
    let configureRental: () -> Void
    let decreaseQuantity: () -> Void
    let increaseQuantity: () -> Void
    let addToCart: () -> Void

    var body: some View {
        VStack(alignment: .leading, spacing: 16) {
            if showRentalSelector {
                RentalConfigurationSummary(
                    rentalMonths: rentalMonths,
                    rentalStartDateLabel: rentalStartDateLabel,
                    rentalEndDateLabel: rentalEndDateLabel,
                    configureAction: configureRental
                )
            }

            if currentQuantity > 0 {
                ProductQuantityControls(
                    currentQuantity: currentQuantity,
                    canIncrease: currentQuantity < stockLimit,
                    isLoading: isLoading,
                    decreaseAction: decreaseQuantity,
                    increaseAction: increaseQuantity
                )
            } else {
                ProductAddToCartButton(
                    isLoading: isLoading,
                    isDisabled: isOutOfStock,
                    label: addButtonLabel,
                    action: addToCart
                )
                .padding(.top, 8)
            }
        }
    }
}
