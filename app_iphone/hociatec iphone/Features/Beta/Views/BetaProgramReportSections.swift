import SwiftUI

struct BetaProgramCreateReportSection: View {
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

struct BetaProgramReportsSection: View {
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
                        BetaReportRow(report: report, viewModel: viewModel)
                    }
                }
            }
        }
    }
}

private struct BetaReportRow: View {
    let report: BetaBugReport
    @ObservedObject var viewModel: BetaProgramViewModel

    var body: some View {
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
