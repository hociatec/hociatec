import Foundation
import Combine
import CoreLocation

struct AddressAutofillResult {
    let address: String
    let postalCode: String
    let city: String
}

@MainActor
final class AddressLocationLookupViewModel: NSObject, ObservableObject {
    @Published var isLoading = false
    @Published var error: String?

    private let manager = CLLocationManager()
    private let geocoder = CLGeocoder()
    private var completion: ((AddressAutofillResult) -> Void)?

    override init() {
        super.init()
        manager.delegate = self
        manager.desiredAccuracy = kCLLocationAccuracyHundredMeters
    }

    func fillFromCurrentLocation(completion: @escaping (AddressAutofillResult) -> Void) {
        guard !isLoading else { return }

        error = nil
        self.completion = completion

        switch manager.authorizationStatus {
        case .authorizedAlways, .authorizedWhenInUse:
            startLocationLookup()
        case .notDetermined:
            manager.requestWhenInUseAuthorization()
        case .restricted, .denied:
            error = "Autorisez la localisation dans les réglages pour remplir l’adresse automatiquement."
        @unknown default:
            error = "La localisation n’est pas disponible sur cet appareil."
        }
    }

    private func startLocationLookup() {
        isLoading = true
        manager.requestLocation()
    }

    private func resolveAddress(from location: CLLocation) {
        geocoder.reverseGeocodeLocation(location) { [weak self] placemarks, geocodeError in
            Task { @MainActor in
                guard let self else { return }

                if let geocodeError {
                    self.isLoading = false
                    self.error = geocodeError.localizedDescription
                    return
                }

                guard let placemark = placemarks?.first else {
                    self.isLoading = false
                    self.error = "Impossible de déterminer une adresse depuis votre position."
                    return
                }

                let streetParts = [
                    placemark.subThoroughfare,
                    placemark.thoroughfare
                ]
                .compactMap { $0?.trimmingCharacters(in: .whitespacesAndNewlines) }
                .filter { !$0.isEmpty }

                let addressLine = streetParts.joined(separator: " ")
                let postalCode = placemark.postalCode?.trimmingCharacters(in: .whitespacesAndNewlines) ?? ""
                let city = placemark.locality?.trimmingCharacters(in: .whitespacesAndNewlines) ?? ""

                guard !addressLine.isEmpty, !postalCode.isEmpty, !city.isEmpty else {
                    self.isLoading = false
                    self.error = "La position a été trouvée, mais l’adresse complète n’a pas pu être récupérée."
                    return
                }

                self.isLoading = false
                self.error = nil
                self.completion?(AddressAutofillResult(address: addressLine, postalCode: postalCode, city: city))
                self.completion = nil
            }
        }
    }
}

extension AddressLocationLookupViewModel: CLLocationManagerDelegate {
    nonisolated func locationManagerDidChangeAuthorization(_ manager: CLLocationManager) {
        Task { @MainActor in
            switch manager.authorizationStatus {
            case .authorizedAlways, .authorizedWhenInUse:
                if completion != nil {
                    startLocationLookup()
                }
            case .restricted, .denied:
                isLoading = false
                error = "Autorisez la localisation dans les réglages pour remplir l’adresse automatiquement."
                completion = nil
            case .notDetermined:
                break
            @unknown default:
                isLoading = false
                error = "La localisation n’est pas disponible sur cet appareil."
                completion = nil
            }
        }
    }

    nonisolated func locationManager(_ manager: CLLocationManager, didUpdateLocations locations: [CLLocation]) {
        guard let location = locations.last else {
            Task { @MainActor in
                isLoading = false
                error = "Impossible de récupérer votre position actuelle."
                completion = nil
            }
            return
        }

        Task { @MainActor in
            resolveAddress(from: location)
        }
    }

    nonisolated func locationManager(_ manager: CLLocationManager, didFailWithError error: Error) {
        Task { @MainActor in
            isLoading = false
            self.error = error.localizedDescription
            completion = nil
        }
    }
}
