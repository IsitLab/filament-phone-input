<?php

namespace IsitLab\FilamentPhoneInput\Tables;

use Closure;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\HtmlString;
use libphonenumber\PhoneNumberFormat;
use Propaganistas\LaravelPhone\Exceptions\NumberParseException;
use IsitLab\FilamentPhoneInput\PhoneInputNumberType;

class PhoneColumn extends TextColumn
{
    protected string | Closure | null $countryColumn = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->displayFormat(PhoneInputNumberType::NATIONAL);
    }

    public function countryColumn(string | Closure $column): static
    {
        $this->countryColumn = $column;

        return $this;
    }

    public function getCountryColumn()
    {
        return $this->evaluate($this->countryColumn);
    }

    public function displayFormat(PhoneInputNumberType $format)
    {
        return $this->formatStateUsing(function ($state, $record) use ($format) {
            try {
                $countryColumn = $this->getCountryColumn();

                $country = [];

                if ($countryColumn) {
                    $country = $record->getAttributeValue($countryColumn);
                }

                $format = $format->toLibPhoneNumberFormat();

                if ($format === PhoneNumberFormat::RFC3966) {
                    $formatted = phone(
                        number: $state,
                        country: $country,
                        format: $format
                    );

                    $national = phone(
                        number: $state,
                        country: $country,
                        format: PhoneNumberFormat::NATIONAL
                    );

                    $html = <<<HTML
                        <a href="$formatted">
                            $national
                        </a>
                    HTML;

                    return new HtmlString($html);
                }

                return phone(
                    number: $state,
                    country: $country,
                    format: $format
                );
            } catch (NumberParseException $e) {
                return $state;
            }
        })->when($format === PhoneInputNumberType::RFC3966, fn (PhoneColumn $column) => $column->disabledClick());
    }
}
