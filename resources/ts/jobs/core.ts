/** Shape returned for each job from GET /api/jobs (subset used client-side). */
export interface PortalJobRow {
  id: number;
  employment_type?: string;
  title?: string;
  location?: string;
  company_name?: string;
}

export function parseJobsApiPayload(raw: unknown): PortalJobRow[] {
  if (raw === null || typeof raw !== "object" || !("jobs" in raw)) {
    return [];
  }
  const jobs = (raw as { jobs: unknown }).jobs;
  if (!Array.isArray(jobs)) {
    return [];
  }
  return jobs
    .map((row): PortalJobRow | null => {
      if (row === null || typeof row !== "object" || !("id" in row)) {
        return null;
      }
      const id = Number((row as { id: unknown }).id);
      if (!Number.isFinite(id)) {
        return null;
      }
      const r = row as Record<string, unknown>;
      return {
        id,
        employment_type: typeof r.employment_type === "string" ? r.employment_type : undefined,
        title: typeof r.title === "string" ? r.title : undefined,
        location: typeof r.location === "string" ? r.location : undefined,
        company_name: typeof r.company_name === "string" ? r.company_name : undefined,
      };
    })
    .filter((j): j is PortalJobRow => j !== null);
}

export function jobCardMatchesFilter(employmentType: string, filterValue: string): boolean {
  if (filterValue === "") {
    return true;
  }
  return employmentType === filterValue;
}
