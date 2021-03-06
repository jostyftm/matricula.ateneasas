<?php
/**
 * Created by PhpStorm.
 * User: Jackson
 * Date: 31/07/2018
 * Time: 7:07 PM
 */

namespace App\Helpers\Utils;


use App\Group;
use App\GroupPensum;
use App\GroupPensumPerformances;
use App\NotesFinal;
use App\NotesParametersPerformances;
use App\ScaleEvaluation;
use App\Workingday;

class Utils
{
    // Clase destinada solo para consultas con validaciones adicionales

    //Para Estadísticas
    public static function query_get_notes_final($institution_id, $group_id, $is_filter)
    {
        if ($is_filter == "true") {
            return NotesFinal::getNotesFilterByAreas($institution_id, $group_id);
        }
        if ($is_filter != "true") {
            return NotesFinal::getNotesFilterByAsignatures($institution_id, $group_id);
        }
    }
    public static function query_get_subjects($group_id, $is_filter)
    {
        if ($is_filter == "true") {
            return GroupPensum::getAreasByGroupX($group_id);
        }
        if ($is_filter != "true") {
            return GroupPensum::getAsignaturesByGroupX($group_id);
        }
    }

    // Relation Performances --> Para Evaluación
    public static function store_relationship_performances($notes_parameters_id, $performances_id, $periods_id, $group_pensum_id, $config_option_id)
    {
        if ($config_option_id == 1) {
            //row
            return GroupPensumPerformances::insertRelationPerformances(
                $performances_id,
                $periods_id,
                $group_pensum_id
            );
        }
        if ($config_option_id == 2) {
            //column
            return NotesParametersPerformances::insertRelationPerformances(
                $notes_parameters_id,
                $performances_id,
                $periods_id,
                $group_pensum_id
            );
        }
    }
    public static function get_relationship_performances($notes_parameters_id, $group_pensum_id, $periods_id, $config_option_id)
    {

        if ($config_option_id == 1) {
            //row
            return GroupPensumPerformances::getRelationPerformances(
                $group_pensum_id,
                $periods_id

            );
        }
        if ($config_option_id == 2) {
            //column
            return $notesPerformances = NotesParametersPerformances::getRelationPerformances(
                $notes_parameters_id,
                $group_pensum_id,
                $periods_id
            );
        }
    }
    public static function delete_relationship_performances($notes_performances_id, $group_performances_id, $config_option_id)
    {
        if ($config_option_id == 1) {
            //row
            return GroupPensumPerformances::deleteRelationPerformances($group_performances_id);
        }
        if ($config_option_id == 2) {
            //column
            return NotesParametersPerformances::deleteRelationPerformances($notes_performances_id);
        }

    }

    // Process Note and Overcomming
    /**
     * Determina que nota regresar, si se ha hecho recuperación.
     */
    public static function process_note($note, $overcoming)
    {
        $noteAux = 0;
        $overcomingAux = 0;
        if ($note > 0) {
            if ($overcoming != null && $overcoming > 0) {
                $overcomingAux = $overcoming;
            }
            $noteAux = $note;
        }

        if ($noteAux > $overcomingAux)
            return round($noteAux,1);
        else
            return round($overcomingAux,1);
    }


}