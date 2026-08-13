import SwiftUI

struct GlobalSearchResultHeader<Destination: View>: View {
    let title: String
    let total: Int
    let query: String
    @ViewBuilder let destination: () -> Destination

    var body: some View {
        HStack {
            Text(total > 0 ? "\(title) (\(total))" : title)
            Spacer()
            if total > 0, !query.isEmpty {
                NavigationLink {
                    destination()
                } label: {
                    Text("Voir tout")
                        .font(.caption.weight(.semibold))
                }
            }
        }
    }
}
