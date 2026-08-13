import SwiftUI

struct MyAppointmentsContent: View {
    let service: AppointmentServing
    @ObservedObject var viewModel: MyAppointmentsViewModel
    @Binding var tab: AppointmentTabFilter

    var body: some View {
        Group {
            if let error = viewModel.error {
                Section { Text(error).foregroundStyle(.red) }
            }

            Section {
                Picker("Afficher", selection: $tab) {
                    ForEach(AppointmentTabFilter.allCases) { filter in
                        Text(filter.label).tag(filter)
                    }
                }
                .pickerStyle(.segmented)
                .accessibilityLabel("Filtre des rendez-vous")
                .accessibilityValue(tab.label)
                .accessibilityHint("Sélectionnez pour filtrer la liste des rendez-vous")

                AppointmentSummaryHeader(
                    upcomingCount: viewModel.upcomingFiltered.count,
                    pastCount: viewModel.pastFiltered.count,
                    cancelledCount: viewModel.cancelledAppointments.count,
                    isLoading: viewModel.isLoading
                )

                if tab == .upcoming, let next = viewModel.nextUpcoming() {
                    AppointmentCard(
                        appointment: next,
                        accentColor: .blue.opacity(0.15)
                    ) {
                        AppointmentDetailScreen(appointment: next, viewModel: viewModel)
                    }
                }
            }

            Section {
                if viewModel.visibleAppointments(for: tab).isEmpty {
                    if !(tab == .upcoming && viewModel.nextUpcoming() != nil) {
                        AppointmentEmptyState(
                            icon: "calendar.badge.exclamationmark",
                            message: viewModel.emptyStateMessage(for: tab)
                        )
                        .frame(maxWidth: .infinity)
                        .listRowInsets(EdgeInsets())
                        .listRowSeparator(.hidden)

                        if tab == .upcoming {
                            NavigationLink {
                                AppointmentBookingView(service: service)
                            } label: {
                                Label("Prendre rendez-vous", systemImage: "calendar.badge.plus")
                                    .fontWeight(.semibold)
                            }
                        }
                    }
                } else {
                    ForEach(viewModel.visibleAppointments(for: tab)) { appointment in
                        AppointmentCard(appointment: appointment) {
                            AppointmentDetailScreen(appointment: appointment, viewModel: viewModel)
                        }
                        .listRowInsets(EdgeInsets())
                        .listRowSeparator(.hidden)
                    }
                }
            }
        }
    }
}
