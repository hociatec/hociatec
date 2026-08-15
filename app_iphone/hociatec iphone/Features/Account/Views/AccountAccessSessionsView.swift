import SwiftUI

struct AccountAccessSessionsView: View {
    @EnvironmentObject private var account: AccountViewModel
    @StateObject private var viewModel: AccountAccessSessionsViewModel

    init(service: AccountServing) {
        _viewModel = StateObject(wrappedValue: AccountAccessSessionsViewModel(service: service))
    }

    var body: some View {
        List {
            if viewModel.isLoading && viewModel.sessions.isEmpty {
                Section {
                    HStack {
                        Spacer()
                        ProgressView("Chargement des accès…")
                        Spacer()
                    }
                }
            } else if viewModel.sessions.isEmpty {
                Section {
                    Text("Aucun accès actif détecté.")
                        .foregroundStyle(.secondary)
                }
            } else {
                Section("Accès actifs (\(viewModel.count))") {
                    ForEach(viewModel.sessions) { session in
                        VStack(alignment: .leading, spacing: 8) {
                            HStack(alignment: .firstTextBaseline, spacing: 8) {
                                Text(session.deviceLabel)
                                    .font(.headline)
                                if session.current {
                                    Text("Cet appareil")
                                        .font(.caption.weight(.semibold))
                                        .padding(.horizontal, 8)
                                        .padding(.vertical, 4)
                                        .background(Color.blue.opacity(0.12))
                                        .foregroundStyle(.blue)
                                        .clipShape(Capsule())
                                }
                            }

                            Text("\(session.platformLabel) • \(session.clientLabel)")
                                .font(.subheadline)
                            Text(session.locationLabel)
                                .font(.subheadline)
                                .foregroundStyle(.secondary)
                            Text("Dernière activité : \(session.lastUsedAt.formatted(date: .abbreviated, time: .shortened))")
                                .font(.footnote)
                                .foregroundStyle(.secondary)

                            Button(role: .destructive) {
                                Task { await viewModel.revoke(session: session) }
                            } label: {
                                if viewModel.revokingSessionID == session.id {
                                    ProgressView()
                                        .frame(maxWidth: .infinity, alignment: .center)
                                } else {
                                    Text("Révoquer cet accès")
                                        .frame(maxWidth: .infinity, alignment: .center)
                                }
                            }
                            .buttonStyle(.bordered)
                            .disabled(viewModel.revokingSessionID != nil)
                        }
                        .padding(.vertical, 4)
                    }
                }
            }
        }
        .navigationTitle("Accès")
        .task {
            await viewModel.load()
        }
        .refreshable {
            await viewModel.load(force: true)
        }
        .onChangeCompat(account.isLoggedIn) { isLoggedIn in
            if !isLoggedIn {
                viewModel.clear()
            }
        }
        .feedbackDialog(error: $viewModel.error, success: $viewModel.successMessage)
    }
}
