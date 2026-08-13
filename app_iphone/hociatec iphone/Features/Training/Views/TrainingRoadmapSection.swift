import SwiftUI

struct TrainingRoadmapSection: View {
    let training: Training

    var body: some View {
        Section("Feuille de route") {
            if training.roadmap.isEmpty {
                Text("Le programme détaillé sera communiqué avec les informations de session.")
                    .foregroundStyle(.secondary)
            } else {
                ForEach(training.roadmap.sorted { $0.position < $1.position }) { item in
                    VStack(alignment: .leading, spacing: 4) {
                        Text("\(item.position). \(item.title)")
                            .fontWeight(.semibold)
                    }
                    .padding(.vertical, 2)
                }
            }
        }
    }
}
