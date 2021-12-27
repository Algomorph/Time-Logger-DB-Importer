# Time-Logger-Web-Chart
Includes:
* A python utility to parse CSV reports produced by the aTimeLogger smartphone app and upload them into an online database.
* A PHP script that handles both incoming data imports from the python utility and serves the data to an online interface
* A simple website using some Javascript (along with ApexCharts & some JQuery plugins over CDN) to display the time-logged data (on a weekly basis for now) in a stacked area chart (with options to show/hide any subset of the time series).
