import SwiftUI

struct ServiceDetailLoadingSection: View {
    var body: some View {
        Section {
            ProgressView("Chargement du service...")
                .frame(maxWidth: .infinity, alignment: .center)
        }
    }
}
