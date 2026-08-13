import SwiftUI

struct TrainingDetailLoadingSection: View {
    var body: some View {
        Section {
            ProgressView("Chargement de la formation...")
        }
    }
}

struct TrainingDetailErrorSection: View {
    let error: String

    var body: some View {
        Section {
            Text(error)
                .foregroundStyle(.red)
        }
    }
}
