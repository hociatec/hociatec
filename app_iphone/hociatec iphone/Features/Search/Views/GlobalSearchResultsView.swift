import SwiftUI

struct GlobalSearchResultsView: View {
    @EnvironmentObject private var container: AppContainer
    @EnvironmentObject private var cart: CartViewModel

    @ObservedObject var viewModel: GlobalSearchViewModel

    var body: some View {
        Group {
            if viewModel.shouldShow(.products) {
                GlobalSearchProductsSection(viewModel: viewModel)
                    .environmentObject(container)
                    .environmentObject(cart)
            }

            if viewModel.shouldShow(.services) {
                GlobalSearchServicesSection(viewModel: viewModel)
                    .environmentObject(container)
            }

            if viewModel.shouldShow(.trainings) {
                GlobalSearchTrainingsSection(viewModel: viewModel)
                    .environmentObject(container)
            }

            if viewModel.shouldShow(.news) {
                GlobalSearchNewsSection(viewModel: viewModel)
                    .environmentObject(container)
            }
        }
    }
}
