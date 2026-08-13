import SwiftUI

struct BetaProgramCampaignsSection: View {
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
                    BetaCampaignRow(campaign: campaign, viewModel: viewModel)
                }
            }
        }
    }
}

private struct BetaCampaignRow: View {
    let campaign: BetaCampaign
    @ObservedObject var viewModel: BetaProgramViewModel

    var body: some View {
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
