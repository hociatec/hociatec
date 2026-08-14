import Foundation

struct APIEnvelope<T: Decodable>: Decodable {
    let status: String
    let data: T
    let message: String?
    let details: [String]?
}

struct APIErrorPayload: Decodable {
    let status: String?
    let message: String?
    let details: [String]?
}

enum APIError: LocalizedError {
    case transport(Error)
    case invalidResponse
    case httpStatus(Int, String)
    case decoding

    var errorDescription: String? {
        switch self {
        case .transport(let error):
            if let urlError = error as? URLError {
                switch urlError.code {
                case .notConnectedToInternet:
                    return "Aucune connexion Internet."
                case .timedOut:
                    return "Le serveur met trop de temps à répondre."
                case .cannotFindHost, .cannotConnectToHost, .dnsLookupFailed, .networkConnectionLost:
                    return "Connexion au serveur impossible."
                case .userAuthenticationRequired, .userCancelledAuthentication:
                    return "Authentification requise."
                case .cancelled:
                    return "Requête annulée."
                default:
                    return "La requête réseau a échoué."
                }
            }

            return "La requête réseau a échoué."
        case .invalidResponse:
            return "Réponse invalide du serveur."
        case .httpStatus(_, let message):
            return message
        case .decoding:
            return "Impossible de lire la réponse du serveur."
        }
    }
}

struct PaginationMeta: Decodable {
    let page: Int
    let perPage: Int
    let total: Int
    let totalPages: Int
}
