#!/usr/bin/python3
from typing import Type
import sys
import mariadb
from pathlib import Path
from ext_argparse import Parameter, ParameterEnum, process_arguments

PROGRAM_EXIT_SUCCESS = 0


class DatabaseParameters(ParameterEnum):
    host = Parameter(default="localhost", arg_type=str, arg_help="Hostname or address of the database server.")
    port = Parameter(default=3306, arg_type=int, arg_help="Port from which the database is served at the host.")
    name = Parameter(default="awesome_database", arg_type=str, arg_help="Name of the database on the server.")
    username = Parameter(default="john_doe", arg_type=str, arg_help="Database username.")
    password = Parameter(default="totally_impenetrable_password", arg_type=str, arg_help="Password for the database.",
                         shorthand="pwd")


class Parameters(ParameterEnum):
    input = Parameter(default="aTimeLogger_report.csv", arg_type=str, arg_help="Path to the input CSV file.",
                      positional=True)

    database = DatabaseParameters


def main() -> int:
    process_arguments(Parameters, "A script to import aTimeLogger data into a database.", "import_csv_settings.yaml", True)
    input_path = Path(Parameters.input.value)
    db: Type[DatabaseParameters] = DatabaseParameters

    try:
        print(db.username.value)
        print(db.password.value)
        print(db.port.value)
        print(db.username.value)
        print(db.name.value)

        connection = mariadb.connect(
            user=db.username.value,
            password=db.password.value,
            host=db.host.value,
            port=db.port.value,
            database=db.name.value
        )
    except mariadb.Error as ex:
        print(f"An error occurred while connecting to MariaDB: {ex}")
        sys.exit(1)

    return PROGRAM_EXIT_SUCCESS


if __name__ == "__main__":
    sys.exit(main())
