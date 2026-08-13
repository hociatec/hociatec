import SwiftUI

struct ServiceDetailLoadingSection: View {
    var body: some View {
        Section {
            ProgressView("Chargement du service...")
                .frame(maxWidth: .infinity, alignment: .center)
        }
    }
}

struct ServiceDetailErrorSection: View {
    let error: String

    var body: some View {
        Section {
            Text(error)
                .foregroundStyle(.red)
        }
    }
}
