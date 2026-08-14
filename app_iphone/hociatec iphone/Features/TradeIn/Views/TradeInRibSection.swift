import SwiftUI

struct TradeInRibSection: View {
    let ribFileName: String?
    let onPickPDF: () -> Void

    var body: some View {
        Section("RIB") {
            Button("Choisir un PDF") {
                onPickPDF()
            }
            .accessibilityHint("Ouvre le sélecteur de fichiers pour ajouter votre RIB au format PDF")

            if let ribFileName {
                Text(ribFileName)
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            }
        }
    }
}
