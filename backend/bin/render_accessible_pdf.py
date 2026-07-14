#!/usr/bin/env python3

from __future__ import annotations

import argparse
from pathlib import Path

from weasyprint import HTML


def main() -> int:
    parser = argparse.ArgumentParser(description="Render accessible PDF/UA from HTML")
    parser.add_argument("input_html", help="Path to source HTML file")
    parser.add_argument("output_pdf", help="Path to generated PDF file")
    args = parser.parse_args()

    source = Path(args.input_html)
    target = Path(args.output_pdf)

    html = HTML(filename=str(source), base_url=str(source.parent))
    html.write_pdf(str(target), pdf_variant="pdf/ua-1", pdf_tags=True)

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
