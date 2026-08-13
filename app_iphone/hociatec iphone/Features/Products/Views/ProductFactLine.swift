import SwiftUI

struct ProductFactLine: View {
    let label: String
    let value: String

    var body: some View {
        Text("\(label): \(value)")
            .multilineTextAlignment(.leading)
    }
}
