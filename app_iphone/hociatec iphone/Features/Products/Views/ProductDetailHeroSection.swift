import SwiftUI

struct ProductDetailHeroView: View {
    let product: Product
    let imageURL: URL?
    let placeholder: AnyView
    let reviewsAverage: Double?
    let reviewsTotal: Int

    var body: some View {
        VStack(alignment: .leading, spacing: 16) {
            ProductDetailHeroImage(imageURL: imageURL, placeholder: placeholder)

            VStack(alignment: .leading, spacing: 8) {
                Text(product.name)
                    .font(.title2)
                    .fontWeight(.bold)
                HStack(spacing: 10) {
                    Label(product.category.name, systemImage: "tag")
                        .font(.footnote)
                        .foregroundStyle(.secondary)
                    if let average = reviewsAverage, reviewsTotal > 0 {
                        ProductReviewSummaryView(average: average, total: reviewsTotal)
                    }
                }
                Text(product.shortDescription)
                    .font(.subheadline)
                    .foregroundStyle(.secondary)
            }

            HStack(spacing: 12) {
                ProductHighlightCard(
                    title: "Disponibilité",
                    value: product.stock > 0 ? "En stock" : "Indisponible",
                    detail: "\(product.stock) unité(s)"
                )
                ProductHighlightCard(
                    title: "Référence",
                    value: product.sku,
                    detail: product.sellingType.label
                )
            }
        }
    }
}

private struct ProductDetailHeroImage: View {
    let imageURL: URL?
    let placeholder: AnyView

    var body: some View {
        if let imageURL {
            AsyncImage(url: imageURL) { phase in
                switch phase {
                case .success(let image):
                    image
                        .resizable()
                        .scaledToFit()
                        .frame(maxWidth: .infinity)
                        .cornerRadius(12)
                case .failure:
                    placeholder
                default:
                    ZStack {
                        RoundedRectangle(cornerRadius: 12)
                            .fill(.gray.opacity(0.1))
                            .frame(height: 220)
                        ProgressView()
                    }
                }
            }
        } else {
            placeholder
                .frame(height: 220)
        }
    }
}
