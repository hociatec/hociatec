import SwiftUI

struct ServiceDetailHeroSectionView: View {
    let service: QuoteService
    let imageURL: URL?

    var body: some View {
        Section {
            ServiceDetailHeroMedia(imageURL: imageURL)

            VStack(alignment: .leading, spacing: 10) {
                Text(service.title)
                    .font(.title2)
                    .fontWeight(.bold)
                Text(serviceDescription)
                    .foregroundStyle(.secondary)
            }
            .padding(.top, 8)
        }
    }

    private var serviceDescription: String {
        service.description?.trimmingCharacters(in: .whitespacesAndNewlines).isEmpty == false
            ? (service.description ?? "")
            : "Les informations détaillées de ce service seront précisées avec votre besoin."
    }
}

private struct ServiceDetailHeroMedia: View {
    let imageURL: URL?

    var body: some View {
        if let imageURL {
            AsyncImage(url: imageURL) { phase in
                switch phase {
                case let .success(image):
                    image
                        .resizable()
                        .scaledToFit()
                        .frame(maxWidth: .infinity, maxHeight: 220)
                        .clipShape(RoundedRectangle(cornerRadius: 16))
                case .failure:
                    ServiceDetailPlaceholder()
                default:
                    ProgressView()
                        .frame(maxWidth: .infinity, minHeight: 180)
                }
            }
            .listRowInsets(EdgeInsets())
        } else {
            ServiceDetailPlaceholder()
        }
    }
}

private struct ServiceDetailPlaceholder: View {
    var body: some View {
        ZStack {
            RoundedRectangle(cornerRadius: 16)
                .fill(Color.gray.opacity(0.08))
            Image(systemName: "wrench.and.screwdriver")
                .font(.system(size: 42))
                .foregroundStyle(.secondary)
        }
        .frame(maxWidth: .infinity, minHeight: 180)
    }
}
