import Foundation

struct ClientDashboardActionBuilder {
    func makeActions(
        quotes: [QuoteSummary],
        appointments: AppointmentList?,
        pendingReviews: [PendingReviewItem],
        trainings: [TrainingEnrollment]
    ) -> [ClientDashboardAction] {
        var built: [ClientDashboardAction] = []

        if let firstReview = pendingReviews.first {
            built.append(
                ClientDashboardAction(
                    id: "pending-reviews",
                    title: pendingReviews.count == 1 ? "Laisser 1 avis produit" : "Laisser \(pendingReviews.count) avis produits",
                    detail: "Commande \(firstReview.orderNumber)",
                    destination: .pendingReviews
                )
            )
        }

        if let nextAppointment = appointments?.upcoming
            .filter({ !$0.isCancelledStatus })
            .sorted(by: { $0.startAt < $1.startAt })
            .first
        {
            built.append(
                ClientDashboardAction(
                    id: "appointments",
                    title: "Préparer votre rendez-vous",
                    detail: DateFormatters.frDateTime.string(from: nextAppointment.startAt),
                    destination: .appointments
                )
            )
        }

        if let quoteToAnswer = quotes.first(where: {
            let status = $0.status.folding(options: .diacriticInsensitive, locale: .current).lowercased()
            return status == "sent" || status == "envoye" || status == "envoyé"
        }) {
            built.append(
                ClientDashboardAction(
                    id: "quotes",
                    title: "Répondre au devis \(quoteToAnswer.number ?? "#\(quoteToAnswer.id)")",
                    detail: "Accepter ou refuser la proposition",
                    destination: .quotes
                )
            )
        }

        if let nextTraining = trainings
            .filter({ $0.status != "cancelled" && $0.scheduledStartsAt >= Date() })
            .sorted(by: { $0.scheduledStartsAt < $1.scheduledStartsAt })
            .first
        {
            built.append(
                ClientDashboardAction(
                    id: "trainings",
                    title: "Voir votre formation à venir",
                    detail: "\(nextTraining.session.training.title) · \(DateFormatters.frDateTime.string(from: nextTraining.scheduledStartsAt))",
                    destination: .trainings
                )
            )
        }

        built.append(contentsOf: secondaryActions)
        return built
    }

    private var secondaryActions: [ClientDashboardAction] {
        [
            ClientDashboardAction(
                id: "favorites",
                title: "Retrouver vos favoris",
                detail: "Accédez rapidement à vos produits enregistrés",
                destination: .favorites
            ),
            ClientDashboardAction(
                id: "orders",
                title: "Suivre vos commandes",
                detail: "Consultez l’état et le détail de vos achats",
                destination: .orders
            ),
            ClientDashboardAction(
                id: "communication-preferences",
                title: "Gérer vos préférences",
                detail: "Choisissez vos moyens de communication",
                destination: .communicationPreferences
            ),
            ClientDashboardAction(
                id: "addresses",
                title: "Mettre à jour vos adresses",
                detail: "Facturation et livraison",
                destination: .addresses
            ),
            ClientDashboardAction(
                id: "support",
                title: "Suivre vos demandes SAV",
                detail: "Retrouvez vos échanges avec Hociatec",
                destination: .support
            ),
            ClientDashboardAction(
                id: "vouchers",
                title: "Consulter vos bons",
                detail: "Vos réductions actives et passées",
                destination: .vouchers
            ),
            ClientDashboardAction(
                id: "audits",
                title: "Suivre vos audits",
                detail: "Demandes envoyées et avancement",
                destination: .audits
            ),
            ClientDashboardAction(
                id: "trade-ins",
                title: "Suivre vos reprises",
                detail: "Offres, décisions et avoirs",
                destination: .tradeIns
            )
        ]
    }
}
