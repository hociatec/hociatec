import SwiftUI

struct BetaProgramLoggedInContent: View {
    @ObservedObject var viewModel: BetaProgramViewModel

    var body: some View {
        Group {
            BetaProgramStatusSection(error: viewModel.error)
            BetaProgramIntroSection()
            BetaProgramProfileSection(viewModel: viewModel)
            BetaProgramCampaignsSection(viewModel: viewModel)
            BetaProgramCreateReportSection(viewModel: viewModel)
            BetaProgramReportsSection(viewModel: viewModel)
        }
    }
}
