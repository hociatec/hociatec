import SwiftUI

struct QuoteCustomLineSection: View {
    @Binding var title: String
    @Binding var unitPrice: String
    @Binding var quantity: Int
    @Binding var description: String
    let onAdd: () -> Void

    private var canAdd: Bool {
        !title.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty
            && QuoteMoneyParser.cents(from: unitPrice) != nil
    }

    var body: some View {
        Section {
            TextField("Titre", text: $title)
            TextField("Prix unitaire (€)", text: $unitPrice)
                .keyboardType(.decimalPad)
            Stepper("Quantité: \(quantity)", value: $quantity, in: 1...999)
            TextEditor(text: $description)
                .frame(minHeight: 120)

            Button("Ajouter") {
                onAdd()
            }
            .disabled(!canAdd)
        }
    }
}
