#!/usr/bin/python3
import sys
from ext_argparse import Parameter, ParameterEnum, process_arguments

PROGRAM_EXIT_SUCCESS = 0


class DatabaseParameters(ParameterEnum):
    host = Parameter(default="localhost", arg_type=str, arg_help="Hostname or address.")
    port = Parameter(default=3306, arg_type=int, arg_help="Port from which the database is served at the host.")
    username = Parameter(default="john_doe", arg_type=str, arg_help="Database username.")
    password = Parameter(default="totally_impenetrable_password", arg_type=str, arg_help="Password for the database.",
                         shorthand="pwd")


class Parameters(ParameterEnum):
    input = Parameter(default="aTimeLogger report.csv", arg_type=str, arg_help="Path to the input CSV file.",
                      positional=True)

    database = DatabaseParameters


def main() -> int:
    process_arguments(Parameters, "A script to import aTimeLogger data into a database.", "import_csv_settings.yaml", True)

    return PROGRAM_EXIT_SUCCESS


if __name__ == "__main__":
    sys.exit(main())
