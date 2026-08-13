import SwiftUI

struct BetaProgramLoggedInContent: View {
    @ObservedObject var viewModel: BetaProgramViewModel

    var body: some View {
        Group {
            if let error = viewModel.error, !error.isEmpty {
                Section { Text(error).foregroundStyle(.red) }
            }

            Section {
                Text("Mon espace bêta")
                    .font(.title3.weight(.bold))
                Text("Gérez votre profil bêta, consultez les campagnes ouvertes et suivez vos signalements.")
                    .foregroundStyle(.secondary)
            }

            BetaProgramProfileSection(viewModel: viewModel)
            BetaProgramCampaignsSection(viewModel: viewModel)
            BetaProgramCreateReportSection(viewModel: viewModel)
            BetaProgramReportsSection(viewModel: viewModel)
        }
    }
}

private struct BetaProgramProfileSection: View {
    @ObservedObject var viewModel: BetaProgramViewModel

    var body: some View {
        Section("Profil bêta") {
            if let profile = viewModel.profile {
                Text(viewModel.statusLabel(for: profile.status))
                    .font(.headline)
                Text(profile.motivation ?? "Motivation non renseignée")
                    .font(.footnote)
                    .foregroundStyle(.secondary)
            } else {
                Text("Aucun profil bêta enregistré pour le moment.")
                    .foregroundStyle(.secondary)
            }

            NavigationLink {
                BetaProfileEditorView(viewModel: viewModel)
            } label: {
                Label(viewModel.profile == nil ? "Créer mon profil bêta" : "Modifier mon profil bêta", systemImage: "person.text.rectangle")
            }

            if viewModel.profile != nil {
                Button("Supprimer mon profil bêta", role: .destructive) {
                    Task { await viewModel.deleteProfile() }
                }
            }
        }
    }
}

private struct BetaProgramCampaignsSection: View {
    @ObservedObject var viewModel: BetaProgramViewModel

    var body: some View {
        Section("Campagnes") {
            if viewModel.isLoading && viewModel.campaigns.isEmpty {
                ProgressView("Chargement...")
            } else if viewModel.campaigns.isEmpty {
                Text("Aucune campagne disponible pour le moment.")
                    .foregroundStyle(.secondary)
            } else {
                ForEach(viewModel.campaigns) { campaign in
                    VStack(alignment: .leading, spacing: 6) {
                        Text(campaign.name)
                            .fontWeight(.semibold)
                        Text(viewModel.campaignLabel(for: campaign.status))
                            .font(.caption)
                            .foregroundStyle(.secondary)
                        Text(campaign.description)
                            .font(.footnote)
                            .foregroundStyle(.secondary)
                    }
                    .padding(.vertical, 4)
                }
            }
        }
    }
}

private struct BetaProgramCreateReportSection: View {
    @ObservedObject var viewModel: BetaProgramViewModel

    var body: some View {
        Section {
            NavigationLink {
                BetaReportComposerView(viewModel: viewModel)
            } label: {
                Label("Créer un signalement bêta", systemImage: "ladybug")
            }
            .disabled(!viewModel.canReport)
        } footer: {
            if !viewModel.canReport {
                Text("Le dépôt de signalements est disponible lorsque votre profil bêta est accepté.")
            }
        }
    }
}

private struct BetaProgramReportsSection: View {
    @ObservedObject var viewModel: BetaProgramViewModel

    var body: some View {
        Section("Mes signalements") {
            if viewModel.reports.isEmpty {
                Text("Aucun signalement pour le moment.")
                    .foregroundStyle(.secondary)
            } else {
                ForEach(viewModel.reports) { report in
                    NavigationLink {
                        BetaReportDetailView(viewModel: viewModel, reportId: report.id)
                    } label: {
                        VStack(alignment: .leading, spacing: 6) {
                            Text(report.title)
                                .fontWeight(.semibold)
                            Text("\(viewModel.severityLabel(for: report.severity)) · \(viewModel.reportStatusLabel(for: report.status))")
                                .font(.caption)
                                .foregroundStyle(.secondary)
                            Text(report.description)
                                .font(.footnote)
                                .foregroundStyle(.secondary)
                                .lineLimit(3)
                        }
                        .padding(.vertical, 4)
                    }
                }
            }
        }
    }
}
