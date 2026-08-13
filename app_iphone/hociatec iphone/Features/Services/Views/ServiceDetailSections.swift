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

struct ServiceDetailHeroSectionView: View {
    let service: ServiceSummary
    let imageURL: URL?

    var body: some View {
        Section {
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

struct ServiceDetailFactsSection: View {
    let service: ServiceSummary

    var body: some View {
        Section {
            HStack(spacing: 12) {
                ServiceDetailFactCard(
                    title: "Base tarifaire",
                    value: PriceFormatter.format(cents: service.priceCents)
                )
                ServiceDetailFactCard(
                    title: "Facturation",
                    value: serviceBillingModeLabel(service.unit)
                )
            }
            HStack(spacing: 12) {
                ServiceDetailFactCard(
                    title: "Durée estimée",
                    value: service.durationLabel ?? "Sur étude"
                )
                ServiceDetailFactCard(
                    title: "TVA",
                    value: "\(Int(service.vatRate.rounded())) %"
                )
            }
        }
    }
}

private struct ServiceDetailFactCard: View {
    let title: String
    let value: String

    var body: some View {
        VStack(alignment: .leading, spacing: 6) {
            Text(title)
                .font(.caption)
                .foregroundStyle(.secondary)
            Text(value)
                .font(.headline)
                .fontWeight(.semibold)
        }
        .frame(maxWidth: .infinity, alignment: .leading)
        .padding(14)
        .background(Color(.secondarySystemBackground))
        .clipShape(RoundedRectangle(cornerRadius: 14))
    }
}

struct ServiceDetailActionsSection: View {
    let container: AppContainer

    var body: some View {
        Section("Actions") {
            NavigationLink {
                QuoteRequestView(viewModel: container.makeQuoteViewModel())
            } label: {
                Label("Demander un devis", systemImage: "doc.badge.plus")
            }

            NavigationLink {
                AppointmentBookingView(service: container.services.appointments)
            } label: {
                Label("Prendre rendez-vous", systemImage: "calendar.badge.plus")
            }
        }
    }
}
