import SwiftUI

struct MySupportRequestsView: View {
    @EnvironmentObject private var container: AppContainer
    @StateObject private var viewModel: SupportViewModel
    @State private var showCreateSheet = false

    init(service: SupportServing) {
        _viewModel = StateObject(wrappedValue: SupportViewModel(service: service))
    }

    var body: some View {
        List {
            Section {
                Button {
                    showCreateSheet = true
                } label: {
                    Label("Nouvelle demande SAV", systemImage: "plus.bubble")
                }
            }

            Section("Mes dossiers") {
                if viewModel.isLoading && viewModel.items.isEmpty {
                    ProgressView("Chargement...")
                } else if viewModel.items.isEmpty {
                    Text("Aucune demande SAV pour le moment.")
                        .foregroundStyle(.secondary)
                } else {
                    ForEach(viewModel.items) { item in
                        VStack(alignment: .leading, spacing: 8) {
                            SupportRequestRow(item: item)
                            NavigationLink {
                                SupportRequestDetailView(viewModel: viewModel, requestId: item.id)
                            } label: {
                                Label("Voir le dossier", systemImage: "arrow.right.circle")
                                    .font(.footnote.weight(.semibold))
                            }
                            .buttonStyle(.borderless)
                        }
                    }

                    if viewModel.isLoading {
                        InlineLoadingStatus(message: "Actualisation des demandes SAV…")
                    }
                }
            }
        }
        .navigationTitle("Mes demandes SAV")
        .sheet(item: $viewModel.sharedFile) { file in
            ActivityView(activityItems: [file.url])
        }
        .task { await viewModel.load() }
        .refreshable { await viewModel.load(force: true) }
        .sheet(isPresented: $showCreateSheet) {
            NavigationStack {
                CreateSupportRequestView(
                    viewModel: viewModel,
                    orders: container.services.orders
                )
            }
        }
        .feedbackDialog(error: $viewModel.error, success: $viewModel.successMessage)
    }
}
