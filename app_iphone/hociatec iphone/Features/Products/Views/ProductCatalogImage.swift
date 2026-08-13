import SwiftUI

struct ProductCatalogImage: View {
    let imageURL: URL?
    let height: CGFloat

    var body: some View {
        AsyncImage(url: imageURL) { phase in
            switch phase {
            case .success(let image):
                image
                    .resizable()
                    .scaledToFill()
                    .frame(height: height)
                    .frame(maxWidth: .infinity)
                    .clipped()
                    .cornerRadius(12)
            case .failure:
                RoundedRectangle(cornerRadius: 12)
                    .fill(.gray.opacity(0.1))
                    .frame(height: height)
                    .overlay(Image(systemName: "photo").foregroundStyle(.secondary))
            default:
                RoundedRectangle(cornerRadius: 12)
                    .fill(.gray.opacity(0.08))
                    .frame(height: height)
                    .overlay(ProgressView())
            }
        }
        .accessibilityHidden(true)
    }
}
