/**
 * Formats a location for display according to these rules:
 * - Always show city name
 * - If street (and/or house number) present, show those too
 * - If location is in a different country than eventLocation, show country
 * - If allowPostal is true (ride popup), postal code is included before city
 */
export function formatLocation(location, eventLocation = null, { allowPostal = false } = {}) {
    if (!location) return '';

    if (!location.city && !location.street) return location.address ?? '';

    const parts = [];

    if (location.street) {
        parts.push(location.house_number
            ? `${location.street} ${location.house_number}`
            : location.street);
    }

    const cityName = location.city ?? '';
    const postalPrefix = allowPostal && location.postal_code ? location.postal_code : null;
    const cityPart = [postalPrefix, cityName || null].filter(Boolean).join(' ');
    if (cityPart) parts.push(cityPart);

    if (
        eventLocation
        && location.country_code
        && eventLocation.country_code
        && location.country_code.toLowerCase() !== eventLocation.country_code.toLowerCase()
    ) {
        try {
            const name = new Intl.DisplayNames(['de'], { type: 'region' }).of(location.country_code.toUpperCase());
            if (name) parts.push(name);
        } catch {
            parts.push(location.country_code.toUpperCase());
        }
    }

    return parts.join(', ') || location.address || '';
}

/** Extracts structured address fields from a Nominatim suggestion object. */
export function locationFromNominatim(s) {
    const cc   = (s.address?.country_code ?? '').toUpperCase().slice(0, 2) || null;
    const city = s.address?.city
        ?? s.address?.town
        ?? s.address?.village
        ?? s.address?.hamlet
        ?? s.address?.municipality
        ?? null;

    return {
        address:      s.display_name,
        latitude:     parseFloat(s.lat),
        longitude:    parseFloat(s.lon),
        country_code: cc,
        street:       s.address?.road ?? s.address?.pedestrian ?? s.address?.footway ?? s.address?.path ?? null,
        house_number: s.address?.house_number ?? null,
        postal_code:  s.address?.postcode ?? null,
        city,
    };
}
