"use client";

import React from "react";
import { Column } from "@once-ui-system/core";
import TableOfContents from "./TableOfContents";

interface TableOfContentsWrapperProps {
  structure: {
    title: string;
    display: boolean;
    items: string[];
  }[];
  about: {
    tableOfContent: {
      display: boolean;
      subItems: boolean;
    };
  };
}

const TableOfContentsWrapper: React.FC<TableOfContentsWrapperProps> = ({ structure, about }) => {
  return (
    <Column
      left="0"
      style={{ top: "50%", transform: "translateY(-50%)" }}
      paddingLeft="24"
      gap="32"
    >
      <TableOfContents structure={structure} about={about} />
    </Column>
  );
};

export default TableOfContentsWrapper; 