import ChartPrimitive from "react-apexcharts";
import useIsDark from "@/hooks/useIsDark";

const COLORS = {
    light: {
        primary: "#C2410C",
        accent: "#5F7F6B",
        amber: "#F59E0B",
        green: "#16a34a",
        red: "#DC2626",
        blue: "#5F7F6B",
        violet: "#5F7F6B",
        pending: "#5F7F6B",
        foreground: "#292524",
        muted: "#78716C",
        border: "#E7E5E4",
        grid: "#E7E5E4",
        heatmapEmpty: "#FFF7ED",
        palette: ["#C2410C", "#5F7F6B", "#16A34A", "#F59E0B", "#DC2626"],
    },
    dark: {
        primary: "#EA6A32",
        accent: "#9AB5A3",
        amber: "#FBBF24",
        green: "#4ADE80",
        red: "#F87171",
        blue: "#9AB5A3",
        violet: "#9AB5A3",
        pending: "#9AB5A3",
        foreground: "#F5F0E8",
        muted: "#C8BFB5",
        border: "#61584F",
        grid: "#61584F",
        heatmapEmpty: "#3A332D",
        palette: ["#EA6A32", "#9AB5A3", "#4ADE80", "#FBBF24", "#F87171"],
    },
};

function hasNoData(series) {
    if (!Array.isArray(series) || series.length === 0) return true;

    const values = series.flatMap((entry) =>
        Array.isArray(entry.data)
            ? entry.data.map((point) =>
                  typeof point === "number"
                      ? point
                      : Number(point?.y ?? point?.value ?? 0),
              )
            : [Number(entry)],
    );

    return values.length === 0 || values.every((value) => !value);
}

export default function Chart({
    type,
    series,
    options = {},
    height,
    emptyMessage = "No data for this period",
    statusColors = false,
    categoryColors = {},
}) {
    const isDark = useIsDark();
    const palette = isDark ? COLORS.dark : COLORS.light;

    if (type !== "heatmap" && hasNoData(series)) {
        return (
            <div
                className="grid w-full place-items-center text-sm text-muted-foreground"
                style={{ height }}
                role="status"
            >
                {emptyMessage}
            </div>
        );
    }

    const colors =
        statusColors || Object.keys(categoryColors).length > 0
            ? (options.labels ?? []).map((label, index) => {
                  const category = label.toLowerCase().trim();
                  const categoryColor = categoryColors[category];
                  if (categoryColor && palette[categoryColor]) {
                      return palette[categoryColor];
                  }
                  if (statusColors) {
                      if (category === "processing") return palette.accent;
                      if (category === "pending") return palette.pending;
                      if (["failed", "failure"].includes(category)) return palette.red;
                      if (["cancel", "cancelled", "canceled"].includes(category)) {
                          return palette.blue;
                      }
                      if (["success", "succeeded", "completed", "paid"].includes(category)) {
                          return palette.green;
                      }
                  }
                  return palette.palette[index % palette.palette.length];
              })
            : palette.palette;

    const chartOptions = {
        ...options,
        chart: {
            ...options.chart,
            background: "transparent",
            fontFamily: "inherit",
            foreColor: palette.muted,
            height,
            toolbar: { ...options.chart?.toolbar, show: false },
        },
        colors: options.colors ?? colors,
        plotOptions:
            type === "heatmap"
                ? {
                      ...options.plotOptions,
                      heatmap: {
                          ...options.plotOptions?.heatmap,
                          colorScale: {
                              ...options.plotOptions?.heatmap?.colorScale,
                              ranges: [
                                  {
                                      from: 0,
                                      to: 0,
                                      color: palette.heatmapEmpty,
                                      name: "No orders",
                                  },
                                  {
                                      from: 1,
                                      to: Number.MAX_SAFE_INTEGER,
                                      color: palette.primary,
                                      name: "Orders",
                                  },
                              ],
                          },
                      },
                  }
                : options.plotOptions,
        grid: {
            ...options.grid,
            borderColor: palette.grid,
        },
        theme: {
            ...options.theme,
            mode: isDark ? "dark" : "light",
        },
    };

    return (
        <div className="min-w-0 w-full overflow-hidden">
            <ChartPrimitive
                type={type}
                height={height}
                series={series}
                options={chartOptions}
            />
        </div>
    );
}
