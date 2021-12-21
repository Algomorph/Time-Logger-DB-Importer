#!/usr/bin/python3
from typing import Type, List
import sys
from pathlib import Path
import requests
from ext_argparse import Parameter, ParameterEnum, process_arguments
import csv
import re

PROGRAM_EXIT_SUCCESS = 0


class Parameters(ParameterEnum):
    input = Parameter(default="aTimeLogger_report.csv", arg_type=str, arg_help="Path to the input CSV file.",
                      positional=True)
    start_from_row = Parameter(default=1, arg_type=int, arg_help="Start processing from this row index "
                                                                 "(NB: row at index 0 is usually the header row)")
    private_password = Parameter(default="password", arg_type=str, arg_help="Password to store private data.")


def main() -> int:
    process_arguments(Parameters, "A script to import aTimeLogger data into a database.", "import_csv_settings.yaml",
                      True)
    input_path = Path(Parameters.input.value)
    print(f"Processing file: {input_path}")
    start_from_row = Parameters.start_from_row.value
    insert_script_url = "http://kramida.com/data/insertdb.php"
    request = requests.post(
        insert_script_url,
        {
            "retrieve_activity_types": 1,
            "private_password": Parameters.private_password.value
        }
    )
    activity_types = request.json()
    activity_map = {}
    for activity_type in activity_types:
        activity_map[activity_type["short_description"]] = activity_type["activity_type_id"]

    screen_keyword_pattern = re.compile(r'\s*\([Ss]creen\)\s*$')
    no_screens_keyword_pattern = re.compile(r'\s*\([Nn]o\s*[Ss]creens?\)\s*$')

    with open(input_path, newline='') as csvfile:
        log_reader = csv.reader(csvfile, delimiter=",", )
        i_row = 0
        for row in log_reader:
            if len(row) == 0 or row[0] == '':
                # must have reached the end of the data, before summary
                break
            if i_row < start_from_row:
                i_row += 1
                continue
            short_description = no_screens_keyword_pattern.sub("", row[0])
            screen_used = False
            if len(screen_keyword_pattern.findall(short_description)) > 0:
                short_description = screen_keyword_pattern.sub("", short_description)
                screen_used = True
            activity_type_id = 0
            if short_description not in activity_map:
                data = {
                    "short_description": short_description,
                    "long_description": "",
                    "screen_used": 1 if screen_used else 0
                }
                response = requests.post(insert_script_url, data)
                data = {
                    "retrieve_activity_types": 1,
                    "short_description": short_description,
                    "private_password": Parameters.private_password.value
                }
                request = requests.post(insert_script_url, data)
                activity_type = request.json()[0]
                activity_type_id = activity_type["activity_type_id"]
            else:
                activity_type_id = activity_map[short_description]

            data = {
                "activity_type": activity_type_id,
                "start": row[2],  # NB: duration is in row 1, we drop it since it's derived data
                "end": row[3],
                "comment": row[4]
            }
            requests.post(insert_script_url, data)
            print(f"processed: {row}")
            i_row += 1

    return PROGRAM_EXIT_SUCCESS


if __name__ == "__main__":
    sys.exit(main())
