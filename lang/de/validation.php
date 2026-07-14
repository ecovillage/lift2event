<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used
    | by the validator class. Some of these rules have multiple versions
    | such as the size rules. Feel free to tweak each of these messages.
    |
    */

    'accepted'             => 'Das Feld :attribute muss akzeptiert werden.',
    'accepted_if'          => 'Das Feld :attribute muss akzeptiert werden, wenn :other den Wert :value hat.',
    'active_url'           => 'Das Feld :attribute muss eine gültige URL sein.',
    'after'                => 'Das Feld :attribute muss ein Datum nach :date sein.',
    'after_or_equal'       => 'Das Feld :attribute muss ein Datum nach oder gleich :date sein.',
    'alpha'                => 'Das Feld :attribute darf nur Buchstaben enthalten.',
    'alpha_dash'           => 'Das Feld :attribute darf nur Buchstaben, Nummern, Binde- und Unterstriche enthalten.',
    'alpha_num'            => 'Das Feld :attribute darf nur Buchstaben und Nummern enthalten.',
    'array'                => 'Das Feld :attribute muss eine Liste sein.',
    'ascii'                => 'Das Feld :attribute darf nur ASCII-Zeichen und Symbole enthalten.',
    'before'               => 'Das Feld :attribute muss ein Datum vor :date sein.',
    'before_or_equal'      => 'Das Feld :attribute muss ein Datum vor oder gleich :date sein.',
    'between'              => [
        'array'   => 'Das Feld :attribute muss zwischen :min und :max Elemente haben.',
        'file'    => 'Das Feld :attribute muss zwischen :min und :max Kilobytes groß sein.',
        'numeric' => 'Das Feld :attribute muss zwischen :min und :max liegen.',
        'string'  => 'Das Feld :attribute muss zwischen :min und :max Zeichen lang sein.',
    ],
    'boolean'              => 'Das Feld :attribute muss entweder \'true\' oder \'false\' sein.',
    'confirmed'            => 'Die Bestätigung von :attribute stimmt nicht überein.',
    'current_password'     => 'Das Passwort ist falsch.',
    'date'                 => 'Das Feld :attribute muss ein gültiges Datum sein.',
    'date_equals'          => 'Das Feld :attribute muss ein Datum gleich :date sein.',
    'date_format'          => 'Das Feld :attribute muss dem gültigen Format :format entsprechen.',
    'decimal'              => 'Das Feld :attribute muss :decimal Dezimalstellen haben.',
    'declined'             => 'Das Feld :attribute muss abgelehnt werden.',
    'declined_if'          => 'Das Feld :attribute muss abgelehnt werden, wenn :other den Wert :value hat.',
    'different'            => 'Die Felder :attribute und :other müssen sich unterscheiden.',
    'digits'               => 'Das Feld :attribute muss :digits Ziffern haben.',
    'digits_between'       => 'Das Feld :attribute muss zwischen :min und :max Ziffern haben.',
    'dimensions'           => 'Das Feld :attribute hat ungültige Bild-Dimensionen.',
    'distinct'             => 'Das Feld :attribute beinhaltet einen bereits vorhandenen Wert.',
    'doesnt_end_with'      => 'Das Feld :attribute darf nicht mit einem der folgenden enden: :values.',
    'doesnt_start_with'    => 'Das Feld :attribute darf nicht mit einem der folgenden beginnen: :values.',
    'email'                => 'Das Feld :attribute muss eine gültige E-Mail-Adresse sein.',
    'ends_with'            => 'Das Feld :attribute muss mit einem der folgenden enden: :values.',
    'enum'                 => 'Der gewählte Wert für :attribute ist ungültig.',
    'exists'               => 'Der gewählte Wert für :attribute ist ungültig.',
    'extensions'           => 'Das Feld :attribute muss eine der folgenden Dateiendungen haben: :values.',
    'file'                 => 'Das Feld :attribute muss eine Datei sein.',
    'filled'               => 'Das Feld :attribute muss einen Wert haben.',
    'gt'                   => [
        'array'   => 'Das Feld :attribute muss mehr als :value Elemente haben.',
        'file'    => 'Das Feld :attribute muss größer als :value Kilobytes sein.',
        'numeric' => 'Das Feld :attribute muss größer als :value sein.',
        'string'  => 'Das Feld :attribute muss länger als :value Zeichen sein.',
    ],
    'gte'                  => [
        'array'   => 'Das Feld :attribute muss mindestens :value Elemente haben.',
        'file'    => 'Das Feld :attribute muss größer oder gleich :value Kilobytes sein.',
        'numeric' => 'Das Feld :attribute muss größer oder gleich :value sein.',
        'string'  => 'Das Feld :attribute muss mindestens :value Zeichen lang sein.',
    ],
    'image'                => 'Das Feld :attribute muss ein Bild sein.',
    'in'                   => 'Der gewählte Wert für :attribute ist ungültig.',
    'in_array'             => 'Das Feld :attribute kommt nicht in :other vor.',
    'integer'              => 'Das Feld :attribute muss eine ganze Zahl sein.',
    'ip'                   => 'Das Feld :attribute muss eine gültige IP-Adresse sein.',
    'ipv4'                 => 'Das Feld :attribute muss eine gültige IPv4-Adresse sein.',
    'ipv6'                 => 'Das Feld :attribute muss eine gültige IPv6-Adresse sein.',
    'json'                 => 'Das Feld :attribute muss ein gültiger JSON-String sein.',
    'lowercase'            => 'Das Feld :attribute muss kleingeschrieben sein.',
    'lt'                   => [
        'array'   => 'Das Feld :attribute muss weniger als :value Elemente haben.',
        'file'    => 'Das Feld :attribute muss kleiner als :value Kilobytes sein.',
        'numeric' => 'Das Feld :attribute muss kleiner als :value sein.',
        'string'  => 'Das Feld :attribute muss kürzer als :value Zeichen sein.',
    ],
    'lte'                  => [
        'array'   => 'Das Feld :attribute darf maximal :value Elemente haben.',
        'file'    => 'Das Feld :attribute muss kleiner oder gleich :value Kilobytes sein.',
        'numeric' => 'Das Feld :attribute muss kleiner oder gleich :value sein.',
        'string'  => 'Das Feld :attribute darf maximal :value Zeichen haben.',
    ],
    'mac_address'          => 'Das Feld :attribute muss eine gültige MAC-Adresse sein.',
    'max'                  => [
        'array'   => 'Das Feld :attribute darf nicht mehr als :max Elemente haben.',
        'file'    => 'Das Feld :attribute darf nicht größer als :max Kilobytes sein.',
        'numeric' => 'Das Feld :attribute darf nicht größer als :max sein.',
        'string'  => 'Das Feld :attribute darf nicht größer als :max Zeichen sein.',
    ],
    'max_digits'           => 'Das Feld :attribute darf nicht mehr als :max Ziffern haben.',
    'mimes'                => 'Das Feld :attribute muss eine Datei des Typs :values sein.',
    'mimetypes'            => 'Das Feld :attribute muss eine Datei des Typs :values sein.',
    'min'                  => [
        'array'   => 'Das Feld :attribute muss mindestens :min Elemente haben.',
        'file'    => 'Das Feld :attribute muss mindestens :min Kilobytes groß sein.',
        'numeric' => 'Das Feld :attribute muss mindestens :min sein.',
        'string'  => 'Das Feld :attribute muss mindestens :min Zeichen lang sein.',
    ],
    'min_digits'           => 'Das Feld :attribute muss mindestens :min Ziffern haben.',
    'missing'              => 'Das Feld :attribute darf nicht vorhanden sein.',
    'missing_if'           => 'Das Feld :attribute darf nicht vorhanden sein, wenn :other den Wert :value hat.',
    'missing_unless'       => 'Das Feld :attribute darf nicht vorhanden sein, außer :other hat den Wert :value.',
    'missing_with'         => 'Das Feld :attribute darf nicht vorhanden sein, wenn :values vorhanden ist.',
    'missing_with_all'     => 'Das Feld :attribute darf nicht vorhanden sein, wenn :values vorhanden sind.',
    'multiple_of'          => 'Das Feld :attribute muss ein Vielfaches von :value sein.',
    'not_in'               => 'Der gewählte Wert für :attribute ist ungültig.',
    'not_regex'            => 'Das Format des Feldes :attribute ist ungültig.',
    'numeric'              => 'Das Feld :attribute muss eine Zahl sein.',
    'password'             => [
        'letters'       => 'Das Feld :attribute muss mindestens einen Buchstaben enthalten.',
        'mixed'         => 'Das Feld :attribute muss mindestens einen Groß- und einen Kleinbuchstaben enthalten.',
        'numbers'       => 'Das Feld :attribute muss mindestens eine Zahl enthalten.',
        'symbols'       => 'Das Feld :attribute muss mindestens ein Sonderzeichen enthalten.',
        'uncompromised' => 'Das angegebene :attribute ist in einem Datenleck aufgetaucht. Bitte wähle ein anderes :attribute.',
    ],
    'present'              => 'Das Feld :attribute muss vorhanden sein.',
    'present_if'           => 'Das Feld :attribute muss vorhanden sein, wenn :other den Wert :value hat.',
    'present_unless'       => 'Das Feld :attribute muss vorhanden sein, außer :other hat den Wert :value.',
    'present_with'         => 'Das Feld :attribute muss vorhanden sein, wenn :values vorhanden ist.',
    'present_with_all'     => 'Das Feld :attribute muss vorhanden sein, wenn :values vorhanden sind.',
    'prohibited'           => 'Das Feld :attribute ist unzulässig.',
    'prohibited_if'        => 'Das Feld :attribute ist unzulässig, wenn :other den Wert :value hat.',
    'prohibited_unless'    => 'Das Feld :attribute ist unzulässig, außer :other kommt in :values vor.',
    'prohibits'            => 'Das Feld :attribute verbietet, dass :other vorhanden ist.',
    'regex'                => 'Das Format des Feldes :attribute ist ungültig.',
    'required'             => 'Das Feld :attribute muss ausgefüllt werden.',
    'required_array_keys'  => 'Das Feld :attribute muss Einträge für :values enthalten.',
    'required_if'          => 'Das Feld :attribute muss ausgefüllt werden, wenn :other den Wert :value hat.',
    'required_if_accepted' => 'Das Feld :attribute muss ausgefüllt werden, wenn :other akzeptiert wurde.',
    'required_if_declined' => 'Das Feld :attribute muss ausgefüllt werden, wenn :other abgelehnt wurde.',
    'required_unless'      => 'Das Feld :attribute muss ausgefüllt werden, außer :other ist :values.',
    'required_with'        => 'Das Feld :attribute muss ausgefüllt werden, wenn :values vorhanden ist.',
    'required_with_all'    => 'Das Feld :attribute muss ausgefüllt werden, wenn :values vorhanden sind.',
    'required_without'     => 'Das Feld :attribute muss ausgefüllt werden, wenn :values nicht vorhanden ist.',
    'required_without_all' => 'Das Feld :attribute muss ausgefüllt werden, wenn keines von :values vorhanden ist.',
    'same'                 => 'Die Felder :attribute und :other müssen übereinstimmen.',
    'size'                 => [
        'array'   => 'Das Feld :attribute muss :size Elemente enthalten.',
        'file'    => 'Das Feld :attribute muss :size Kilobytes groß sein.',
        'numeric' => 'Das Feld :attribute muss gleich :size sein.',
        'string'  => 'Das Feld :attribute muss :size Zeichen lang sein.',
    ],
    'starts_with'          => 'Das Feld :attribute muss mit einem der folgenden beginnen: :values.',
    'string'               => 'Das Feld :attribute muss eine Zeichenkette sein.',
    'timezone'             => 'Das Feld :attribute muss eine gültige Zeitzone sein.',
    'ulid'                 => 'Das Feld :attribute muss ein gültiger ULID sein.',
    'unique'               => 'Der Wert für :attribute wird bereits verwendet.',
    'uploaded'             => 'Der Upload von :attribute ist fehlgeschlagen.',
    'uppercase'            => 'Das Feld :attribute muss großgeschrieben sein.',
    'url'                  => 'Das Feld :attribute muss eine gültige URL sein.',
    'uuid'                 => 'Das Feld :attribute muss ein gültiger UUID sein.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | text with something more readable, such as "E-Mail-Adresse" instead
    | of "email". This simply helps us make the message more expressive.
    |
    */

    'attributes' => [
        'name'                  => 'Name',
        'email'                 => 'E-Mail-Adresse',
        'password'              => 'Passwort',
        'password_confirmation' => 'Passwortbestätigung',
        'current_password'      => 'aktuelles Passwort',
        'phone'                 => 'Telefonnummer',
        'start_at'              => 'Beginn',
        'end_at'                => 'Ende',
        'outbound_at'           => 'Hinfahrt',
        'return_at'             => 'Rückfahrt',
        'seats'                 => 'Plätze',
        'info'                  => 'Infos',
        'type'                  => 'Typ',
        'direction'             => 'Richtung',
        'contact_methods'       => 'Kontaktmöglichkeiten',
        'location'              => 'Ort',
        'location.address'      => 'Adresse',
        'location.latitude'     => 'Breitengrad',
        'location.longitude'    => 'Längengrad',
        'location.country_code' => 'Ländercode',
        'preferred_language'    => 'bevorzugte Sprache',
        'q'                     => 'Suchbegriff',
    ],

];
