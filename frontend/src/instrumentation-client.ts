const PERFORMANCE_MEASURE_ERROR_PATTERN = /(negative time stamp|does not exist)/i;

if (typeof window !== "undefined" && typeof window.performance?.measure === "function") {
  const originalMeasure = window.performance.measure.bind(window.performance);

  window.performance.measure = ((...args: Parameters<Performance["measure"]>) => {
    try {
      return originalMeasure(...args);
    } catch (error) {
      const message = String(
        error && typeof error === "object" && "message" in error
          ? (error as { message?: unknown }).message
          : error
      );

      if (PERFORMANCE_MEASURE_ERROR_PATTERN.test(message)) {
        return undefined as unknown as ReturnType<Performance["measure"]>;
      }

      throw error;
    }
  }) as Performance["measure"];
}
