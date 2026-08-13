import SwiftUI

struct AboutStoryView: View {
    var body: some View {
        List {
            Section {
                Text("Hociatec accompagne la vente et la location de matériel informatique, les services numériques, l'assistance, les audits et la formation.")
                Text("L'objectif est de proposer un accompagnement concret, lisible et réactif, aussi bien pour les particuliers que pour les professionnels.")
                Text("L'activité couvre les interventions partout en France, avec une intervention sous 2 heures en Ile-de-France lorsque la situation le permet.")
            }
        }
        .navigationTitle("Notre histoire")
    }
}

struct OpeningHoursView: View {
    private let entries: [(String, String)] = [
        ("Lundi", "09h00 - 20h00"),
        ("Mardi", "09h00 - 20h00"),
        ("Mercredi", "09h00 - 20h00"),
        ("Jeudi", "09h00 - 20h00"),
        ("Vendredi", "09h00 - 20h00"),
        ("Samedi", "09h00 - 17h00"),
        ("Dimanche", "Fermé")
    ]

    var body: some View {
        List {
            Section("Horaires d'ouverture") {
                ForEach(entries, id: \.0) { label, hours in
                    LabeledContent(label, value: hours)
                }
            }
        }
        .navigationTitle("Horaires")
    }
}

struct SocialLinksView: View {
    private let links: [(String, String)] = [
        ("Facebook", "https://www.facebook.com/hociatec"),
        ("LinkedIn", "https://www.linkedin.com/company/hociatec"),
        ("TikTok", "https://www.tiktok.com/@hociatec"),
        ("X", "https://x.com/hociatec"),
        ("Instagram", "https://www.instagram.com/hociatec")
    ]

    var body: some View {
        List {
            ForEach(links, id: \.0) { label, href in
                Link(destination: URL(string: href)!) {
                    HStack {
                        Text(label)
                        Spacer()
                        Image(systemName: "arrow.up.right.square")
                            .foregroundStyle(.secondary)
                    }
                }
            }
        }
        .navigationTitle("Réseaux sociaux")
    }
}
