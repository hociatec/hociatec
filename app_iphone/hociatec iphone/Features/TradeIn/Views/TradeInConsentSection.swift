import SwiftUI

struct TradeInConsentSection: View {
    @Binding var consent: Bool

    var body: some View {
        Section {
            Toggle("J’accepte le traitement de ma demande", isOn: $consent)
        }
    }
}
