<?php

namespace Baspa\Buienradar\Enum;

enum MeasuringStation: string
{
    /** @deprecated Buienradar no longer publishes this station, so it always yields null. */
    case ARCEN = 'Meetstation Arcen';
    case ARNHEM = 'Meetstation Arnhem';
    case BERKHOUT = 'Meetstation Berkhout';
    case DE_BILT = 'Meetstation De Bilt';
    case DEN_HELDER = 'Meetstation Den Helder';
    case EINDHOVEN = 'Meetstation Eindhoven';
    case ELL = 'Meetstation Ell';
    /** @deprecated Use {@see self::GILZE_RIJEN}. */
    case GILZE_RIJEEN = 'Meetstation Gilze Rijen';
    case GOES = 'Meetstation Goes';
    case GROENLO_HUPSEL = 'Meetstation Groenlo-Hupsel';
    case GRONINGEN = 'Meetstation Groningen';
    case HEINO = 'Meetstation Heino';
    case HERWIJNEN = 'Meetstation Herwijnen';
    case HOEK_VAN_HOLLAND = 'Meetstation Hoek van Holland';
    case HOOGEVEEN = 'Meetstation Hoogeveen';
    case HOORN_TERSCHELLING = 'Meetstation Hoorn Terschelling';
    case HORST = 'Meetstation Horst';
    /** @deprecated Use {@see self::HOUTRIBDIJK}. */
    case HOULTRIBDIJK = 'Meetstation Houtribdijk';
    case IJMUIDEN = 'Meetstation IJmuiden';
    case LAUWERSOOG = 'Meetstation Lauwersoog';
    case LEEUWARDEN = 'Meetstation Leeuwarden';
    case LELYSTAD = 'Meetstation Lelystad';
    case LOPIK_CABAUW = 'Meetstation Lopik-Cabauw';
    case MAASTRICHT = 'Meetstation Maastricht';
    case MARKNESSE = 'Meetstation Marknesse';
    case NIEUW_BEERTA = 'Meetstation Nieuw Beerta';
    case ROTTERDAM = 'Meetstation Rotterdam';
    case ROTTERDAM_GEULHAVEN = 'Meetstation Rotterdam Geulhaven';
    case SCHIPHOL = 'Meetstation Schiphol';
    case STAVOREN = 'Meetstation Stavoren';
    case TEXELHORS = 'Meetstation Texelhors';
    case TWENTE = 'Meetstation Twente';
    case VLIELAND = 'Meetstation Vlieland';
    case VLISSINGEN = 'Meetstation Vlissingen';
    case VOLKEL = 'Meetstation Volkel';
    case VOORSCHOTEN = 'Meetstation Voorschoten';
    case WESTDORPE = 'Meetstation Westdorpe';
    case WIJDENES = 'Meetstation Wijdenes';
    case WIJK_AAN_ZEE = 'Meetstation Wijk aan Zee';
    case WOENSDRECHT = 'Meetstation Woensdrecht';
    case ZEEPLATFORM_F3 = 'Meetstation Zeeplatform F-3';

    const GILZE_RIJEN = self::GILZE_RIJEEN;

    const HOUTRIBDIJK = self::HOULTRIBDIJK;
}
