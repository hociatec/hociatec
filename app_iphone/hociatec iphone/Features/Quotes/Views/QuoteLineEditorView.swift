import SwiftUI

struct QuoteLineEditorView: View {
    @Binding var item: QuoteDraftItem
    @Environment(\.dismiss) private var dismiss
    @State private var unitPriceText: String = ""

    var body: some View {
        Form {
            Section {
                if item.isCustom {
                    TextField("Titre", text: Binding(
                        get: { item.title ?? "" },
                        set: { item.title = $0 }
                    ))
                    TextField("Prix unitaire (€)", text: $unitPriceText)
                        .keyboardType(.decimalPad)
                } else {
                    Text(item.displayTitle)
                    if let unitPriceCents = item.unitPriceCents {
                        LabeledContent("Prix unitaire") {
                            Text(PriceFormatter.format(cents: unitPriceCents))
                        }
                    }
                }
            }

            Section {
                Stepper("Quantité: \(item.quantity)", value: $item.quantity, in: 1...999)
            }

            Section {
                TextEditor(text: $item.description)
                    .frame(minHeight: 120)
            }
        }
        .navigationTitle("Modifier")
        .navigationBarTitleDisplayMode(.inline)
        .toolbar {
            ToolbarItem(placement: .confirmationAction) {
                Button("OK") {
                    if item.isCustom, let cents = QuoteMoneyParser.cents(from: unitPriceText) {
                        item.unitPriceCents = cents
                    }
                    dismiss()
                }
            }
        }
        .onAppear {
            unitPriceText = QuoteMoneyParser.string(fromCents: item.unitPriceCents)
        }
    }
}
